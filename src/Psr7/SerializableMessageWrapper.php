<?php

/*
 * Copyright 2019 The Apigee Mock Client PHP Authors.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Apigee\MockClient\Psr7;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The serializable HTTP message wrapper.
 *
 * This class is necessary because the stream in the guzzle request/response
 * classes are not serializable for database storage. This class deconstructs a
 * response into primitive types for serialization and reconstructs it on
 * unserialization.
 */
class SerializableMessageWrapper {

  /**
   * The original HTTP message.
   *
   * @var \Psr\Http\Message\ResponseInterface
   */
  private $message;

  /**
   * SerializableResponseWrapper constructor.
   *
   * @param \Psr\Http\Message\MessageInterface $message
   *   The original HTTP message.
   */
  public function __construct(MessageInterface $message) {
    if (!$message instanceof ResponseInterface) {
        throw new \InvalidArgumentException('SerializableMessageWrapper only supports ResponseInterface objects.');
    }
    $this->message = $message;
  }

  /**
   * Get the original HTTP message.
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function __serialize(): array {
    return [
        'body' => (string) $this->message->getBody(),
        'protocol_version' => $this->message->getProtocolVersion(),
        'headers' => $this->message->getHeaders(),
        'status_code' => $this->message->getStatusCode(),
        'reason_phrase' => $this->message->getReasonPhrase(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __unserialize(array $data): void {
    $this->message = new Response(
        $data['status_code'],
        $data['headers'],
        Utils::streamFor($data['body']),
        $data['protocol_version'],
        $data['reason_phrase']
    );
  }

}