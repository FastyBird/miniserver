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
FastyBird connector worker
"""

# Python base dependencies
import asyncio
import logging
import signal
import time
from abc import ABC
from threading import Thread
from types import FrameType
from typing import Optional

# Worker dependencies
from fastybird_exchange.client import IClient
from kink import di

# Worker libs
from fastybird_miniserver.exchange.queue import ConsumerQueue, PublisherQueue


class Worker(ABC):  # pylint: disable=too-many-instance-attributes
    """
    Worker service

    @package        FastyBird:MiniServer!
    @module         workers/worker

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    __stopped: bool = False

    __consumer_queue: Optional[ConsumerQueue] = None
    __publisher_queue: Optional[PublisherQueue] = None
    __exchange_client: Optional[IClient] = None

    __exchange_consumer_thread: Optional[Thread] = None
    __exchange_publisher_thread: Optional[Thread] = None
    __exchange_client_thread: Optional[Thread] = None

    _logger: logging.Logger

    __SHUTDOWN_WAITING_DELAY: float = 3.0

    # -----------------------------------------------------------------------------

    def __init__(
        self,
        consumer_queue: Optional[ConsumerQueue] = None,
        publisher_queue: Optional[PublisherQueue] = None,
        exchange_client: Optional[IClient] = None,
        logger: logging.Logger = logging.getLogger("dummy"),
    ) -> None:
        self.__publisher_queue = publisher_queue
        self.__consumer_queue = consumer_queue
        self.__exchange_client = exchange_client

        # self.__connector_thread = Thread(name="Connector thread", daemon=True, target=self.connector_loop)
        if self.__exchange_client is not None:
            self.__exchange_client_thread = Thread(
                name="Exchange client thread",
                daemon=True,
                target=self.exchange_client_loop,
            )

        if self.__consumer_queue is not None:
            self.__exchange_consumer_thread = Thread(
                name="Exchange consumer thread",
                daemon=True,
                target=self.exchange_consumer_loop,
            )

        if self.__publisher_queue is not None:
            self.__exchange_publisher_thread = Thread(
                name="Exchange publisher thread",
                daemon=True,
                target=self.exchange_publisher_loop,
            )

        # Configure signal handlers
        signal.signal(signal.SIGINT, self.sigterm_handler)
        signal.signal(signal.SIGTERM, self.sigterm_handler)

        self._logger = logger

        self.__stopped = False

        # Register event loop into DI container
        di["loop"] = asyncio.get_event_loop()

        # Register worker coroutines
        # asyncio.ensure_future(self.exchange_client_loop())
        asyncio.ensure_future(self.worker_loop())

        # self.__connector_thread.start()
        if self.__exchange_client_thread is not None:
            self.__exchange_client_thread.start()
        if self.__exchange_consumer_thread is not None:
            self.__exchange_consumer_thread.start()
        if self.__exchange_publisher_thread is not None:
            self.__exchange_publisher_thread.start()

        # while not self.__stopped:
        #     # Just to keep worker running
        #     time.sleep(1)

        # Run loop with coroutines
        di["loop"].run_forever()

    # -----------------------------------------------------------------------------

    @property
    def is_stopped(self) -> bool:
        """Check if worker is stopped or not"""
        return self.__stopped

    # -----------------------------------------------------------------------------

    async def worker_loop(self) -> None:
        """Connector coroutine"""

    # -----------------------------------------------------------------------------

    def exchange_client_loop(self) -> None:
        """Data exchange client coroutine"""
        if self.__exchange_client is None:
            return

        try:
            self.__exchange_client.start()

            while True:
                if self.__stopped:
                    break

                self.__exchange_client.handle()

                # await asyncio.sleep(0.001)
                time.sleep(0.001)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "The exchange client worker has been unexpectedly stopped.",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

            self.stop()

    # -----------------------------------------------------------------------------

    def exchange_consumer_loop(self) -> None:
        """Data exchange consumer coroutine"""
        try:
            while True:
                self.__consumer_queue.handle()

                # All records have to be processed before thread is closed
                if self.__stopped and not self.__consumer_queue.has_unfinished_items():
                    break

                # await asyncio.sleep(0.001)
                time.sleep(0.001)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "The exchange consumer worker has been unexpectedly stopped.",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

            self.stop()

    # -----------------------------------------------------------------------------

    def exchange_publisher_loop(self) -> None:
        """Data exchange publisher coroutine"""
        try:
            while True:
                self.__publisher_queue.handle()

                # All records have to be processed before thread is closed
                if self.__stopped and not self.__publisher_queue.has_unfinished_items():
                    break

                # await asyncio.sleep(0.001)
                time.sleep(0.001)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "The exchange publisher worker has been unexpectedly stopped.",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

            self.stop()

    # -----------------------------------------------------------------------------

    def stop(self) -> None:
        """Worker stop handler"""
        if self.__stopped:
            return

        # Mark worker as stopped
        self.__stopped = True

        try:
            if self.__exchange_client is not None:
                # Terminate all exchange services
                self.__exchange_client.stop()

            if self.__exchange_client_thread is not None:
                self.__wait_for_thread_to_close(thread_service=self.__exchange_client_thread)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "Unexpected exception was thrown during stopping exchange client process",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

        try:
            if self.__exchange_consumer_thread is not None:
                self.__wait_for_thread_to_close(thread_service=self.__exchange_consumer_thread)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "Unexpected exception was thrown during stopping exchange consumer process",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

        try:
            if self.__exchange_publisher_thread is not None:
                self.__wait_for_thread_to_close(thread_service=self.__exchange_publisher_thread)

        except Exception as ex:  # pylint: disable=broad-except
            self._logger.exception(ex)
            self._logger.error(
                "Unexpected exception was thrown during stopping exchange publisher process",
                extra={
                    "exception": {
                        "message": str(ex),
                        "code": type(ex).__name__,
                    },
                },
            )

        if di["loop"].is_running():
            di["loop"].stop()

        self._logger.info("===========================")
        self._logger.info("The worker has been stopped")

    # -----------------------------------------------------------------------------

    def sigterm_handler(
        self,
        signals: int,  # pylint: disable=unused-argument
        frame: Optional[FrameType],  # pylint: disable=unused-argument
    ) -> None:
        """Handle termination request"""
        self.stop()

    # -----------------------------------------------------------------------------

    def __wait_for_thread_to_close(self, thread_service: Thread) -> None:
        now = time.time()

        waiting_for_closing = True

        # Wait until thread is fully terminated
        while waiting_for_closing and time.time() - now < self.__SHUTDOWN_WAITING_DELAY:
            if not thread_service.is_alive():
                waiting_for_closing = False
