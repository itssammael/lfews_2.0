<?php

namespace App\Services;

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ResponseFactory;
use ModbusTcpClient\Utils\Types;
use Exception;

class ModbusService
{
    /**
     * Read holding registers from a Modbus device.
     *
     * @param string $host
     * @param int $port
     * @param int $startAddress
     * @param int $quantity
     * @param int $unitId
     * @param float $timeout
     * @return array
     * @throws Exception
     */
    public function readModbusData(
        string $host = '192.168.41.10',
        int $port = 100,
        int $startAddress = 1,
        int $quantity = 6,
        int $unitId = 1,
        float $timeout = 1.0
    ): array {
        $connection = BinaryStreamConnection::getBuilder()
            ->setHost($host)
            ->setPort($port)
            ->setConnectTimeoutSec($timeout)
            ->build();

        try {
            $packet = new ReadHoldingRegistersRequest($startAddress, $quantity, $unitId);
            $binaryData = $connection->connect()->sendAndReceive($packet);
            $response = ResponseFactory::parseResponseOrThrow($binaryData);

            $data = [];
            foreach ($response->getWords() as $word) {
                $data[] = $word->getInt16();
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        } finally {
            $connection->close();
        }
    }

    public function readHoldingRegisters(
        string $host = "192.168.41.10",
        int $port = 100,
        int $startAddress = 6,
        int $quantity = 3,
        int $unitId = 1,
        float $timeout = 1
    ): array {
        return $this->readModbusData($host, $port, $startAddress, $quantity, $unitId, $timeout);
    }

    /**
     * Generic method to execute a Modbus packet.
     *
     * @param string $host
     * @param int $port
     * @param mixed $packet
     * @param float $timeout
     * @return mixed
     * @throws Exception
     */
    public function executePacket(string $host, int $port, $packet, float $timeout = 1.5)
    {
        $connection = BinaryStreamConnection::getBuilder()
            ->setHost($host)
            ->setPort($port)
            ->setConnectTimeoutSec($timeout)
            ->build();

        try {
            $binaryData = $connection->connect()->sendAndReceive($packet);
            return ResponseFactory::parseResponseOrThrow($binaryData);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $connection->close();
        }
    }
}
