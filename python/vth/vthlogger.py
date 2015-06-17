#!/usr/local/bin/python
"""
VTH logger module
"""
import logging
def initLogger(name, lev, fname):
    # create logger VTH debug logger
    logger = logging.getLogger(name)
    logger.setLevel(lev)
    # create file handler
    fh = logging.FileHandler(fname)
    # create console handler with a higher log level
    ch = logging.StreamHandler()
    ch.setLevel(lev)
    # create formatter and add it to the handlers
    formatter = logging.Formatter("%(asctime)s|%(name)s|%(levelname)s" +
                                  "[%(filename)s:%(funcName)s:%(lineno)d]:\n" +
                                  "%(message)s")
    fh.setFormatter(formatter)
    ch.setFormatter(formatter)
    # add the handlers to the logger
    logger.addHandler(fh)
    logger.addHandler(ch)
    return logger

try:
    if VTHDebugLogger is None :
        VTHDebugLogger = initLogger("VTH_DBG", logging.DEBUG, "vthdbg.log")
except:
    VTHDebugLogger = initLogger("VTH_DBG", logging.DEBUG, "vthdbg.log")
