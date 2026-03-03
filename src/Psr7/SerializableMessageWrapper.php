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

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The serializable HTTP message wrapper.
 *
 * This class is necessary because the stream in the guzzle request/response
 * classes are not serializable for database storage. This class deconstructs a
 * message into primitive types for serialization and reconstructs it on
 * unserialization. It supports both RequestInterface and ResponseInterface.
 */
class SerializableMessageWrapper {

  /**
   * The original HTTP message.
   *
   * @var \Psr\Http\Message\MessageInterface
   */
  private $message;

  /**
   * SerializableResponseWrapper constructor.
   *
   * @param \Psr\Http\Message\MessageInterface $message
   *   The original HTTP message.
   */
  public function __construct(MessageInterface $message) {
    $this->message = $message;
  }

  /**
   * Get the original HTTP message.
   */
  public function getMessage(): MessageInterface {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function __serialize(): array {
    $data = [
      'body' => (string) $this->message->getBody(),
      'protocol_version' => $this->message->getProtocolVersion(),
      'headers' => $this->message->getHeaders(),
    ];

    if ($this->message instanceof RequestInterface) {
      $data['is_request'] = TRUE;
      $data['method'] = $this->message->getMethod();
      $data['uri'] = (string) $this->message->getUri();
    }

    if ($this->message instanceof ResponseInterface) {
      $data['is_response'] = TRUE;
      $data['status_code'] = $this->message->getStatusCode();
      $data['reason_phrase'] = $this->message->getReasonPhrase();
    }

    return ['message_data' => $data];
  }

  /**
   * {@inheritdoc}
   */
  public function __unserialize(array $data): void {
    $messageData = $data['message_data'];
    $body = Utils::streamFor($messageData['body']);
    $headers = $messageData['headers'] ?? [];
    $protocolVersion = $messageData['protocol_version'];

    if (!empty($messageData['is_request'])) {
      $this->message = new Request(
        $messageData['method'],
        $messageData['uri'],
        $headers,
        $body,
        $protocolVersion
      );
    }
    elseif (!empty($messageData['is_response'])) {
      $this->message = new Response(
        $messageData['status_code'] ?? 200,
        $headers,
        $body,
        $protocolVersion,
        $messageData['reason_phrase'] ?? ''
      );
    }
    else {
      // Fallback for older data or unknown types.
      $this->message = new Response(200, $headers, $body, $protocolVersion);
    }
  }

}