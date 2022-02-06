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
FastyBird worker exchange queue module
"""

# Python base dependencies
import logging
from queue import Full as QueueFull
from queue import Queue
from typing import Dict, List, Optional, Union

# App dependencies
from fastybird_exchange.publisher import IPublisher, IQueue
from fastybird_metadata.routing import RoutingKey
from fastybird_metadata.types import ConnectorOrigin, ModuleOrigin, PluginOrigin
from kink import inject


@inject(alias=IQueue)
class ExchangeQueue(IQueue):
    """
    Data exchange publisher queue interface

    @package        FastyBird:MiniServer!
    @module         exchange/queue

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __queue: Queue

    __publishers: List[IPublisher]

    __logger: logging.Logger

    # -----------------------------------------------------------------------------

    def __init__(self, logger: logging.Logger = logging.getLogger("dummy")) -> None:
        self.__queue = Queue(maxsize=1000)

        self.__logger = logger

    # -----------------------------------------------------------------------------

    def set_publishers(self, publishers: List[IPublisher]) -> None:
        """Set publishers to queue"""
        self.__publishers = publishers

    # -----------------------------------------------------------------------------

    def append(
        self,
        origin: Union[ModuleOrigin, PluginOrigin, ConnectorOrigin],
        routing_key: RoutingKey,
        data: Optional[Dict],
    ) -> None:
        """Append new item to que"""
        try:
            self.__queue.put(
                item={
                    "origin": origin,
                    "routing_key": routing_key,
                    "data": data,
                }
            )

        except QueueFull:
            self.__logger.error("Exchange processing queue is full. New messages could not be added")

    # -----------------------------------------------------------------------------

    def handle(self) -> None:
        """Proces one item from queue"""
        if not self.__queue.empty():
            item = self.__queue.get()

            if isinstance(item, dict):
                origin: Union[ModuleOrigin, PluginOrigin, ConnectorOrigin, None] = None

                if ModuleOrigin.has_value(str(item.get("origin"))):
                    origin = ModuleOrigin(str(item.get("origin")))

                elif PluginOrigin.has_value(str(item.get("origin"))):
                    origin = PluginOrigin(str(item.get("origin")))

                elif ConnectorOrigin.has_value(str(item.get("origin"))):
                    origin = ConnectorOrigin(str(item.get("origin")))

                if origin is None:
                    return

                for publisher in self.__publishers:
                    publisher.publish(
                        origin=origin,
                        routing_key=RoutingKey(item.get("routing_key")),
                        data=item.get("data"),
                    )

    # -----------------------------------------------------------------------------

    def has_unfinished_items(self) -> bool:
        """Check if queue has some unfinished items"""
        return not self.__queue.empty()
