﻿#!/usr/bin/python3
# -*- coding: utf-8 -*-

#
# Copyright 2019 Raffaello Di Martino
# From a work of Matthew Wall on fileparser driver
#
# weewx driver that reads data from a file coming from ecowitt_gateway
# https://github.com/iz0qwm/ecowitt_http_gateway/
#
# This program is free software: you can redistribute it and/or modify it under
# the terms of the GNU General Public License as published by the Free Software
# Foundation, either version 3 of the License, or any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT
# ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
# FOR A PARTICULAR PURPOSE.
#
# See http://www.gnu.org/licenses/

# This driver will read data from a file generated by the https://github.com/iz0qwm/ecowitt_http_gateway/
# name=value pair, for example:
#
# outTemp=79.3
# barometer=29.719
# pressure=29.719
# outHumidity=70
# windSpeed=0.00
# windDir=277
# windGust=0.00
# rainRate=0.000
# rain_total=6.903
# inTemp=79.7
# inHumidity=76
# radiation=0.00
# UV=0
# windchill=
# dewpoint=
# extraTemp1=78.44
# extraHumid1=74
# extraTemp2=78.51
# extraHumid2=75
# soilTemp1=0
# txBatteryStatus=
# rainBatteryStatus=1.6
# outTempBatteryStatus=0
#
# The units must be in the weewx.US unit system:
#   degree_F, inHg, inch, inch_per_hour, mile_per_hour
#
# To use this driver, put this file in the weewx drivers directory (i.e. /usr/share/weewx/weewx/drivers ), then make
# the following changes to weewx.conf:
#
# [Station]
#     station_type = ecowitt
# [ecowitt]
#     poll_interval = 65                    # number of seconds, just a little more the GW1000 update time
#     path = /var/log/ecowitt/weewx.txt     # location of data file
#     driver = weewx.drivers.ecowitt
#     mode = normal                         # normal = use the ecowitt_http_gateway - server = without gateway, GW1000 configured to send data
                                            # to address:port
#     address = ip_address_of_weewx_server
#     port = 9999
#
# The variables in the file have the same names from those in the database
# so you don't need a mapping section
#
# But if the variables in the file have names different from those in the database
# schema, then create a mapping section called label_map.  This will map the
# variables in the file to variables in the database columns.  For example:
#
# [ecowitt]
#     ...
#     [[label_map]]
#         temp = outTemp
#         humi = outHumidity
#         in_temp = inTemp
#         in_humid = inHumidity

from __future__ import with_statement
import syslog
import time
import BaseHTTPServer
import SocketServer
import re
import Queue
import threading
import urlparse

import weewx.drivers

DRIVER_NAME = 'ecowitt'
DRIVER_VERSION = '1.2'

DEFAULT_ADDR = '192.168.1.109'
DEFAULT_PORT = 80


def logmsg(dst, msg):
    syslog.syslog(dst, 'ecowitt: %s' % msg)


def logdbg(msg):
    logmsg(syslog.LOG_DEBUG, msg)


def loginf(msg):
    logmsg(syslog.LOG_INFO, msg)


def logerr(msg):
    logmsg(syslog.LOG_ERR, msg)


def _obfuscate_passwords(msg):
    return re.sub(r'(PASSWORD|PASSKEY)=[^&]+', r'\1=XXXX', msg)


def _get_as_float(d, s):
    v = None
    if s in d:
        try:
            v = float(d[s])
        except ValueError as e:
            logerr("cannot read value for '%s': %s" % (s, e))
    return v


def loader(config_dict, engine):
    return ecowittDriver(**config_dict[DRIVER_NAME])


queue = Queue.Queue()


class ecowittDriver(weewx.drivers.AbstractDevice):

    """weewx driver for ecowitt GW1000"""

    def __init__(self, **stn_dict):

        # where to find the data file

        self.path = stn_dict.get('path', '/var/log/ecowitt/weewx.txt')

        # how often to poll the weather data file, seconds

        self.poll_interval = float(stn_dict.get('poll_interval', 2.5))

        # mapping from variable names to weewx names

        self.label_map = stn_dict.get('label_map', {})
        self.last_rain = None
        self.mode = stn_dict.get('mode')
        self.address = stn_dict.get('address')
        self.port = stn_dict.get('port')

        loginf('data file is %s' % self.path)
        loginf('polling interval is %s' % self.poll_interval)
        loginf('label map is %s' % self.label_map)

        if self.mode == 'normal':
            loginf('mode is %s' % self.mode)
        elif self.mode == 'server':

            # self.genLoopPackets()

            loginf('mode is %s' % self.mode)
            loginf('address is %s' % self.address)
            loginf('port is %s' % self.port)

            handler = None

            self._server = self.TCPServer(self.address, self.port,
                    handler)

            self._server_thread = \
                threading.Thread(target=self.run_server)
            self._server_thread.setDaemon(True)
            self._server_thread.setName('ServerThread')
            self._server_thread.start()
            self._queue_timeout = int(stn_dict.pop('queue_timeout', 10))
        else:

            raise Exception("unrecognized mode '%s'" % self.mode)

    def run_server(self):
        self._server.run()

    def stop_server(self):
        self._server.stop()
        self._server = None

    def get_queue(self):
        return queue

    class Server(object):

        def run(self):
            pass

        def stop(self):
            pass

    class Handler(BaseHTTPServer.BaseHTTPRequestHandler):

        def get_response(self):

            # default reply is a simple 'OK' string

            return 'OK'

        def reply(self):

            # standard reply is HTTP code of 200 and the response string

            response = bytes(self.get_response())
            self.send_response(200)
            self.send_header('Content-Length', str(len(response)))
            self.end_headers()
            self.wfile.write(response)

        def do_POST(self):

            # get the payload from an HTTP POST

            length = int(self.headers['Content-Length'])
            data = str(self.rfile.read(length))
            logdbg('POST: %s' % _obfuscate_passwords(data))
            queue.put(data)
            self.reply()

        def do_PUT(self):
            pass

        def do_GET(self):

            # get the query string from an HTTP GET

            data = urlparse.urlparse(self.path).query
            logdbg('GET: %s' % _obfuscate_passwords(data))
            queue.put(data)
            self.reply()

        # do not spew messages on every connection

        def log_message(self, _format, *_args):
            pass

    class TCPServer(Server, SocketServer.TCPServer):

        daemon_threads = True
        allow_reuse_address = True

        def __init__(
            self,
            address,
            port,
            handler,
            ):
            if handler is None:
                handler = ecowittDriver.Handler
            loginf('listen on %s:%s' % (address, port))
            SocketServer.TCPServer.__init__(self, (address, int(port)),
                    handler)

        def run(self):
            logdbg('start tcp server')
            self.serve_forever()

        def stop(self):
            logdbg('stop tcp server')
            self.shutdown()
            self.server_close()

    def genLoopPackets(self):
        while True:
            if self.mode == 'normal':

            # read whatever values we can get from the file

                data = {}
                try:
                    with open(self.path) as f:
                        for line in f:
                            eq_index = line.find('=')
                            name = line[:eq_index].strip()
                            value = line[eq_index + 1:].strip()
                            data[name] = value
                except Exception, e:
                    logerr('read failed: %s' % e)

            # map the data into a weewx loop packet

                _packet = {'dateTime': int(time.time() + 0.5),
                           'usUnits': weewx.US}
                for vname in data:
                    _packet[self.label_map.get(vname, vname)] = \
                        _get_as_float(data, vname)

                self._augment_packet(_packet)
                yield _packet
                time.sleep(self.poll_interval)
            elif self.mode == 'server':
                try:
                    pkt = dict()
                    data = self.get_queue().get(True,
                            self._queue_timeout)
                    logdbg('raw data: %s' % data)
                    parts = data.split('&')
                    for x in parts:
                        if not x:
                            continue
                        if '=' not in x:
                            loginf("unexpected un-assigned variable '%s'"
                                    % x)
                            continue
                        (n, v) = x.split('=')
                        n = n.strip()
                        v = v.strip()
                        try:
                            rain_total = None
                            if n == 'tempf':
                                pkt['outTemp'] = float(v)
                            elif n == 'baromrelin':
                                pkt['barometer'] = float(v)
                            elif n == 'baromabsin':
                                pkt['pressure'] = float(v)
                            elif n == 'humidity':
                                pkt['outHumidity'] = float(v)
                            elif n == 'windspeedmph':
                                pkt['windSpeed'] = float(v)
                            elif n == 'windgustmph':
                                pkt['windGust'] = float(v)
                            elif n == 'winddir':
                                pkt['winddir'] = float(v)
                            elif n == 'rainratein':
                                pkt['rainRate'] = float(v)
                            elif n == 'totalrainin':
                                pkt['rain_total'] = float(v)
                            elif n == 'tempinf':
                                pkt['inTemp'] = float(v)
                            elif n == 'humidityin':
                                pkt['inHumidity'] = float(v)
                            elif n == 'solarradiation':
                                pkt['radiation'] = float(v)
                            elif n == 'uv':
                                pkt['uv'] = float(v)
                            elif n == 'dewptf':
                                pkt['dewpoint'] = float(v)
                            elif n == 'temp1f':
                                pkt['extraTemp1'] = float(v)
                            elif n == 'temp2f':
                                pkt['extraTemp2'] = float(v)
                            elif n == 'humidity1':
                                pkt['extraHumid1'] = float(v)
                            elif n == 'humidity2':
                                pkt['extraHumid2'] = float(v)
                            elif n == 'soilmoisture1':
                                pkt['soilTemp1'] = float(v)
                            elif n == 'wh80batt':
                                pkt['consBatteryVoltage'] = float(v)
                                if float(v) < 2.5:
                                    pkt['windBatteryStatus'] = 1.0
                                if float(v) > 2.5:
                                    pkt['windBatteryStatus'] = 0.0
                            elif n == 'wh40batt':
                                pkt['supplyVoltage'] = float(v)
                                if float(v) < 1.0:
                                    pkt['rainBatteryStatus'] = 1.0
                                if float(v) > 1.0:
                                    pkt['rainBatteryStatus'] = 0.0
                            elif n == 'batt1':
                                pkt['outTempBatteryStatus'] = float(v)
                            else:
                                loginf("unknown element '%s' with value '%s'"
                                         % (n, v))
                        except (ValueError, IndexError) as e:
                            logerr("decode failed for %s '%s': %s"
                                   % (n, v, e))

                    pkt['rain'] = \
                        weewx.wxformulas.calculate_rain(pkt['rain_total'
                            ], self.last_rain)
                    self.last_rain = pkt['rain_total']

                    pkt['windchill'] = 35.74 + 0.6215 * pkt['outTemp'] \
                        + (0.4275 * pkt['outTemp'] - 35.75) \
                        * pkt['windSpeed'] ** 0.16
                    outTemp_c = (pkt['outTemp'] - 32) * 5 / 9
                    dewpoint_c = (pkt['outHumidity'] / 100) ** 0.125 \
                        * (112 + 0.9 * outTemp_c) + 0.1 * outTemp_c \
                        - 112
                    pkt['dewpoint'] = dewpoint_c * (9 / 5.0) + 32

                    logdbg('raw packet: %s' % pkt)

                # map the data into a weewx loop packet

                    _packet = {
                        'dateTime': int(time.time() + 0.5),
                        'usUnits': weewx.US,
                        'rain': pkt['rain'],
                        'outTemp': pkt['outTemp'],
                        'barometer': pkt['barometer'],
                        'pressure': pkt['pressure'],
                        'humidity': pkt['outHumidity'],
                        'windSpeed': pkt['windSpeed'],
                        'windGust': pkt['windGust'],
                        'windDir': pkt['winddir'],
                        'rainRate': pkt['rainRate'],
                        'inTemp': pkt['inTemp'],
                        'inHumidity': pkt['inHumidity'],
                        'radiation': pkt['radiation'],
                        'uv': pkt['uv'],
                        'windchill': pkt['windchill'],
                        'dewpoint': pkt['dewpoint'],
                        'extraTemp1': pkt['extraTemp1'],
                        'extraHumid1': pkt['extraHumid1'],
                        'extraTemp2': pkt['extraTemp2'],
                        'extraHumid2': pkt['extraHumid2'],
                        'soilTemp1': pkt['soilTemp1'],
                        'consBatteryVoltage': pkt['consBatteryVoltage'
                                ],
                        'supplyVoltage': pkt['supplyVoltage'],
                        'windBatteryStatus': pkt['windBatteryStatus'],
                        'rainBatteryStatus': pkt['rainBatteryStatus'],
                        'outTempBatteryStatus': pkt['outTempBatteryStatus'
                                ],
                        }

                    logdbg('decoded packet: %s' % _packet)

                    yield _packet
                except Queue.Empty:

                    logdbg('empty queue')

                time.sleep(self.poll_interval)
            else:
                raise Exception("unrecognized mode '%s'" % self.mode)

    def _augment_packet(self, packet):
        packet['rain'] = \
            weewx.wxformulas.calculate_rain(packet['rain_total'],
                self.last_rain)
        self.last_rain = packet['rain_total']

    @property
    def hardware_name(self):
        return 'ecowitt'


# To test this driver, run it directly as follows:
#   PYTHONPATH=python /usr/share/weewx/weewx/drivers/ecowitt.py

if __name__ == '__main__':
    import weeutil.weeutil
    driver = ecowittDriver()
    for packet in driver.genLoopPackets():
        print weeutil.weeutil.timestamp_to_string(packet['dateTime']), \
            packet
