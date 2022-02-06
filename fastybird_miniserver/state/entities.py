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
FastyBird worker states module entities
"""

# Python base dependencies
import uuid
from typing import Dict, List, Optional, Union

# App dependencies
from fastybird_devices_module.state.property import (
    IChannelPropertyState,
    IDevicePropertyState,
    IPropertyState,
)
from fastybird_redisdb_storage_plugin.state import StorageItem
from fastybird_triggers_module.state.action import IActionState
from fastybird_triggers_module.state.condition import IConditionState


class PropertyState(IPropertyState, StorageItem):
    """
    Base property state

    @package        FastyBird:MiniServer!
    @module         state/entities

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    @property
    def id(self) -> uuid.UUID:  # pylint: disable=invalid-name
        """Property unique identifier"""
        return self.item_id

    # -----------------------------------------------------------------------------

    @property
    def actual_value(self) -> Optional[str]:
        """Value which is actually stored in property"""
        if "actual_value" in self._raw:
            return str(self._raw.get("actual_value")) if self._raw.get("actual_value", None) is not None else None

        return None

    # -----------------------------------------------------------------------------

    @property
    def expected_value(self) -> Optional[str]:
        """Value which is expected to be stored in property"""
        if "expected_value" in self._raw:
            return str(self._raw.get("expected_value")) if self._raw.get("expected_value", None) is not None else None

        return None

    # -----------------------------------------------------------------------------

    @property
    def pending(self) -> bool:
        """Flag informing that property is pending value update"""
        if "pending" in self._raw:
            return bool(self._raw.get("pending", False))

        return False

    # -----------------------------------------------------------------------------

    @staticmethod
    def create_fields() -> Dict[Union[str, int], Union[str, int, float, bool, None]]:
        """Storage entity fields used during entity creation"""
        return {
            0: "id",
            "actual_value": None,
            "expected_value": None,
            "pending": False,
            "created_at": None,
            "updated_at": None,
        }

    # -----------------------------------------------------------------------------

    @staticmethod
    def update_fields() -> List[str]:
        """Storage entity fields used during entity update"""
        return [
            "actual_value",
            "expected_value",
            "pending",
            "updated_at",
        ]

    # -----------------------------------------------------------------------------

    def to_dict(self) -> Dict:
        """Transform record to dictionary"""
        return {
            "id": self.id.__str__(),
            "actual_value": self.actual_value,
            "expected_value": self.expected_value,
            "pending": self.pending,
        }

    # -----------------------------------------------------------------------------

    def __copy__(self) -> object:
        return type(self)(item_id=self.id, raw=self.raw)


class DevicePropertyState(IDevicePropertyState, PropertyState):
    """
    Device property state

    @package        FastyBird:MiniServer!
    @module         state/entities

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    def __eq__(self, other: object) -> bool:
        if not isinstance(other, DevicePropertyState):
            raise Exception("")

        return (
            self.id == other.id
            and self.actual_value == other.actual_value
            and self.expected_value == other.expected_value
            and self.pending == other.pending
        )


class ChannelPropertyState(IChannelPropertyState, PropertyState):
    """
    Channel property state

    @package        FastyBird:MiniServer!
    @module         state/entities

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    def __eq__(self, other: object) -> bool:
        if not isinstance(other, ChannelPropertyState):
            raise Exception("")

        return (
            self.id == other.id
            and self.actual_value == other.actual_value
            and self.expected_value == other.expected_value
            and self.pending == other.pending
        )


class ActionState(IActionState, StorageItem):
    """
    Action state

    @package        FastyBird:MiniServer!
    @module         state/entities

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    @property
    def id(self) -> uuid.UUID:  # pylint: disable=invalid-name
        """Property unique identifier"""
        return self.item_id

    # -----------------------------------------------------------------------------

    @property
    def is_triggered(self) -> bool:
        """Is action triggered"""
        if "is_triggered" in self._raw:
            return bool(self._raw.get("is_triggered")) if self._raw.get("is_triggered", None) is not None else False

        return False

    # -----------------------------------------------------------------------------

    @staticmethod
    def create_fields() -> Dict[Union[str, int], Union[str, int, float, bool, None]]:
        """Storage entity fields used during entity creation"""
        return {
            0: "id",
            "is_triggered": False,
            "created_at": None,
            "updated_at": None,
        }

    # -----------------------------------------------------------------------------

    @staticmethod
    def update_fields() -> List[str]:
        """Storage entity fields used during entity update"""
        return [
            "is_triggered",
            "updated_at",
        ]

    # -----------------------------------------------------------------------------

    def to_dict(self) -> Dict:
        """Transform record to dictionary"""
        return {
            "id": self.id.__str__(),
            "is_triggered": self.is_triggered,
        }

    # -----------------------------------------------------------------------------

    def __copy__(self) -> object:
        return type(self)(item_id=self.id, raw=self.raw)


class ConditionState(IConditionState, StorageItem):
    """
    Condition state

    @package        FastyBird:MiniServer!
    @module         state/entities

    @author         Adam Kadlec <adam.kadlec@fastybird.com>
    """

    @property
    def id(self) -> uuid.UUID:  # pylint: disable=invalid-name
        """Property unique identifier"""
        return self.item_id

    # -----------------------------------------------------------------------------

    @property
    def is_fulfilled(self) -> bool:
        """Is condition fulfilled"""
        if "is_fulfilled" in self._raw:
            return bool(self._raw.get("is_fulfilled")) if self._raw.get("is_fulfilled", None) is not None else False

        return False

    # -----------------------------------------------------------------------------

    @staticmethod
    def create_fields() -> Dict[Union[str, int], Union[str, int, float, bool, None]]:
        """Storage entity fields used during entity creation"""
        return {
            0: "id",
            "is_fulfilled": False,
            "created_at": None,
            "updated_at": None,
        }

    # -----------------------------------------------------------------------------

    @staticmethod
    def update_fields() -> List[str]:
        """Storage entity fields used during entity update"""
        return [
            "is_fulfilled",
            "updated_at",
        ]

    # -----------------------------------------------------------------------------

    def to_dict(self) -> Dict:
        """Transform record to dictionary"""
        return {
            "id": self.id.__str__(),
            "is_fulfilled": self.is_fulfilled,
        }

    # -----------------------------------------------------------------------------

    def __copy__(self) -> object:
        return type(self)(item_id=self.id, raw=self.raw)
