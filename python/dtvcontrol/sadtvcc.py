#!/usr/bin/env python

# Description: Windows serial port control of a DirecTV receiver with Python
# Note: Device configuration and control codes are defined in .ini file
# Author: Jeffrey Clark
# License: GNU GPLv3
# Thanks:
#    Jim Storch - original idea
#    Kevin Timmerman and Any Ellsworth - device control documentation

import serial
import os, sys, time, ConfigParser

debug = 0

class Receiver:
    START_RESPONSE = 240     #'\xf0'
    BAD_COMMAND = 241        #'\xf1'
    PROCESSING_COMMAND = 242 #'\xf2'
    INPUT_TIMEOUT = 243      #'\xf3'
    END_RESPONSE = 244       #'\xf4'
    FAILED_COMMAND = 245     #'\xf5'
    
    START_COMMAND = 250      #'\xfa'
    COMMAND_PROMPT = 251     #'\xfb'
    BUFFER_UNDERFLOW = 253   #'\xfd'
    BUFFER_OVERFLOW = 255    #'\xff'

    CMD_UNKNOWN1 = 132
    CMD_SHOW = 133
    CMD_HIDE = 134        
    CMD_GET_CHANNEL = 135
    CMD_SIGNAL_STRENGTH = 144
    CMD_GET_DATE = 145
    CMD_ENABLE_IR = 147
    CMD_DISABLE_IR = 148
    CMD_SET_CHANNEL = 166
    CMD_SEND_KEY = 165

    def __init__(self, port, speed, receiver_options):
        self.ser = serial.Serial(port, speed, xonxoff=0, rtscts=0, \
                bytesize=8, parity='N', stopbits=1, timeout=5)
        self.ser.flushInput()
        self.ser.flushOutput()
        self.options = receiver_options

    def send_key(self, key):
        try:
            command = self.options["pre_key"]
            command.insert(0, self.options["cmd_send_key"])
        except:
            print >>sys.stderr, "ERROR: pre_key must be mapped to a list."
            sys.exit(1)

        try:            
            command.append(self.options["key_" + str(key)])
        except:
            print >>sys.stderr, "ERROR: key mapping not found or invalid"
            sys.exit(1)
            
            if debug: print >>sys.stderr, ".. mapping key [" + str(key) + "] to code " + str(self.options["key_" + str(key)])
            response = self.dss_command(command)
            
    def dss_command(self, command):
        self.ser.flushInput()
        command.insert(0, self.options["pre_request"])

        while len(command) > 0:
            a = command.pop(0)
            if debug: print >>sys.stderr, ".. sending " + str(a)
            self.ser.write(chr(a))
            
        return self.__get_reply()

    def __get_reply(self):
        Started = False
        msgparts = []
        timeout = 10
        while timeout > 0:
            byte = ord(self.ser.read(1))
            
            if debug: print >>sys.stderr, ".. received: %d" %byte
            
            if byte == self.START_RESPONSE and not Started:
                Started = True
                if debug: print >>sys.stderr, ".. start packet found"
                continue
            elif not Started:
                if byte == self.BAD_COMMAND:
                    if debug: print >>sys.stderr, ".. got bad command!"
                else:
                    if debug: print >>sys.stderr, ".. got %d" %byte
                return False
            elif Started:
                if byte == self.END_RESPONSE:
                    if debug: print >>sys.stderr, ".. end packet found"
                    break
                if byte == self.INPUT_TIMEOUT:
                    if debug: print >>sys.stderr, ".. got input timeout"
                    break
                if byte == self.FAILED_COMMAND:
                    if debug: print >>sys.stderr, ".. got failed command"
                    return self.FAILED_COMMAND
                if byte == self.PROCESSING_COMMAND:
                    if debug: print >>sys.stderr, ".. got processing command"
                    continue
            
            msgparts.append(byte)
            timeout -= 1
        return msgparts

    def setChannel(self, channel):
        if debug: print >>sys.stderr, "::setChannel(" + str(channel) + ")"
        response = rcvr.dss_command([rcvr.options['cmd_set_channel'], (channel / 256), (channel % 256), 255, 255])
            
    def getChannel(self):
        if debug: print >>sys.stderr, "::getChannel"
        response = rcvr.dss_command([rcvr.options['cmd_get_channel']])
        channel = response[0] * 256 + response[1]
        if len(response) == 4 and response[3] <> 255:
            channel = channel, ".", response[2] * 256 + response[3]
        return channel

        
    def read_config():
        config = ConfigParser.SafeConfigParser()

        if os.path.exists('sadtvcc.ini'):
            inifile = 'sadtvcc.ini'
        else:
            print >>sys.stderr, 'ERROR: no sadtvcc.ini'
            sys.exit(1)

        if not config.read(inifile):
            print >>sys.stderr, 'ERROR: failed to read %s' %inifile
            sys.exit(1)

        return config

if __name__ == "__main__":

    banner = """sadtvcc v0.1, Copyright Jeffrey Clark 2010\n"""
    usage = """Usage: sadtvcc (device_name) (channel)"""

    if len(sys.argv) < 3:
        print banner, usage
        sys.exit(2)

    try:
        device_name = sys.argv[1]
    except:
        print >>sys.stderr, 'ERROR: invalid device argument'
        sys.exit(1)
            
    try:
        tune_channel = int(sys.argv[2])
    except:
        print >>sys.stderr, 'ERROR: invalid channel argument'
        sys.exit(1)

    try:
        key_press = sys.argv[3]
    except:
        key_press = bool(0)
        
    config = read_config()

    try:
        debug = int(eval(config.get(device_name, 'debug')))
    except:
        debug = 1

    if not config.has_section(device_name):
        print >>sys.stderr, 'ERROR: device %s is not configured.  Create section in INI file first.' %device_name
        sys.exit(1)

    try:
        serial_port = config.get(device_name, 'serial_port')
        serial_speed = config.getint(device_name, 'serial_speed')
        receiver_name = config.get(device_name, 'receiver')
    except:
        print >>sys.stderr, "ERROR: Device is not correctly configured in INI file."
        sys.exit(1)

    if not config.has_section("MODEL_" + receiver_name):
        print >>sys.stderr, 'ERROR: Model %s was not found in INI file.' %receiver_name
        sys.exit(1)

    receiver_options = {}
    for s in config.items("MODEL_" + receiver_name, True):
        try:
            receiver_options[s[0]] = eval(s[1])
        except:
            receiver_options[s[0]] = s[1]
        
    rcvr = Receiver(serial_port, serial_speed, receiver_options)

    if key_press:
        rcvr.send_key(key_press)
    else:
        tuning = 1
        while tuning < rcvr.options["tune_retries"]:
            tuning = tuning + 1
            
            current_channel = rcvr.getChannel()
            if current_channel == tune_channel:
                print >>sys.stderr, device_name + ' sucessfully tuned channel ' + str(tune_channel)
                sys.exit(0)
            else:
                status = rcvr.setChannel(tune_channel)
               
    sys.exit(1)
