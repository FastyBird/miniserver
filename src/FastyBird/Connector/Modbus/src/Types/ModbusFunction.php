<?php declare(strict_types = 1);

namespace FastyBird\Connector\Modbus\Types;

/**
 * RTU function types
 */
enum ModbusFunction: int
{

	case READ_COIL = 0x01;

	case READ_DISCRETE = 0x02;

	case READ_HOLDINGS_REGISTERS = 0x03;

	case READ_INPUTS_REGISTERS = 0x04;

	case WRITE_SINGLE_COIL = 0x05;

	case WRITE_SINGLE_HOLDING_REGISTER = 0x06;

	case WRITE_MULTIPLE_COILS = 0x1F;

	case WRITE_MULTIPLE_HOLDINGS_REGISTERS = 0x10;

}
