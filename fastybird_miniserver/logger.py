#!/usr/bin/python3

#     Copyright 2021. FastyBird s.r.o.
#
#     Licensed under the Apache License, Version 2.0 (the "License");
#     you may not use this file except in compliance with the License.
#     You may obtain a copy of the License at
#
#         http://www.apache.org/licenses/LICENSE-2.0
#
#     Unless required by applicable law or agreed to in writing, software
#     distributed under the License is distributed on an "AS IS" BASIS,
#     WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
#     See the License for the specific language governing permissions and
#     limitations under the License.

"""
FastyBird worker logger module
"""

# Python base dependencies
import logging
import sys
from logging.handlers import TimedRotatingFileHandler

FORMATTER = logging.Formatter(
    fmt="[%(asctime)s][%(name)s][%(levelname)s] - %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)


def get_console_handler() -> logging.StreamHandler:
    """Create logger console handler"""
    console_handler = logging.StreamHandler(stream=sys.stdout)
    console_handler.setFormatter(fmt=FORMATTER)

    return console_handler


def get_file_handler(channel: str) -> TimedRotatingFileHandler:
    """Create logger file handler"""
    file_handler = TimedRotatingFileHandler(filename=f"./logs/{channel}.log", when="midnight")
    file_handler.setFormatter(fmt=FORMATTER)

    return file_handler


def get_logger(channel: str, level: int = logging.DEBUG) -> logging.Logger:
    """Get logger for given name"""
    logger = logging.getLogger(name=channel)

    logger.setLevel(level=level)  # better to have too much log than not enough

    logger.addHandler(hdlr=get_console_handler())
    logger.addHandler(hdlr=get_file_handler(channel=channel))

    # with this pattern, it's rarely necessary to propagate the error up to parent
    logger.propagate = False

    return logger
