<?php

namespace App\Service;

use App\Entity\Messages;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\Serializer\SerializerInterface;

class WebsocketService implements MessageComponentInterface
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private SerializerInterface $serializer;
    private JWTEncoderInterface $jwtEncoder;
    private \SplObjectStorage $clients;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        SerializerInterface $serializer,
        JWTEncoderInterface $jwtEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->serializer = $serializer;
        $this->jwtEncoder = $jwtEncoder;
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $from)
    {
        // Attach the new connection to the clients storage
        $this->clients->attach($from);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $msg = json_decode($msg, true);

        if(!isset($msg['type'])){
            $from->send(json_encode([
                'type' => 'error',
                'data' => 'Key \'type\'is required'
            ]));
            return;
        }
        
        if ($msg['type'] ==='authentication'){
            // Handle authentication
            $this->handleAuthenticate($from, $msg['data']);
            return;
        }

        // Check if the user is authenticated
        if (!isset($this->clients[$from]['user'])) {
            $from->send(json_encode([
                'type' => 'error',
                'data' => 'Unauthorized'

            ]));
            return;
        }

        if ($msg['type'] === 'conversation.message.created') {
            // Handle authentication
            $this->handleChatMessage($from, $msg['data']);
            return;
        }

    }

    private function handleAuthenticate(ConnectionInterface $from, string $token): void
    {
        try {
            // Decode the JWT token to get the payload
            $payload = $this->jwtEncoder->decode($token);
            $email = $payload['username'] ?? null; 
            var_dump($payload);
            if (!$email) {
                $from->send(json_encode([
                    'type' => 'error',
                    'data' => 'Invalid Token Payload'
                ]));
                $from->close();
                return;
            }

            // Retrieve the user from the database
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $from->send(json_encode([
                    'type' => 'error',
                    'data' => 'User not found'
                ]));
                $from->close();
                return;
            }

            // Store the user in the clients storage associated with the connection
            $this->clients[$from] = ['user' => $user];
        } catch (\Exception $e) {
            $from->send(json_encode([
                'type' => 'error',
                'data' => 'Authentification failed'
            ]));
            $from->close();
        }
    }

    private function handleChatMessage(ConnectionInterface $from, array $data): void
    {
        if (!isset($data['content'], $data['receiverId'], $data['senderId'])) {
            $from->send(json_encode([
                'type' => 'error',
                'data' => 'Invalid message data'
            ]));
            return;
        }


        // Create a new message entity
        $message = new Messages();
        $message->setContent($data['content']);
        $message->setSentDate(new \DateTimeImmutable());

        //  Find the sender <user
        $sender = $this->userRepository->find($data['senderId']);
        if (!$sender) {
            $from->send(json_encode([
                'type' => 'error',
                'data' => 'sender not found'
            ]));
            return;
        }

        $message->setSender($sender);


        // Find the receiver user
        $receiver = $this->userRepository->find($data['receiverId']);
        if (!$receiver) {
            $from->send(json_encode([
                'type' => 'error',
                'data' => 'receiver not found'
            ]));
            return;
        }

        $message->setReceiver($receiver);

        // Persist the message to the database
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // Serialize the message for sending
        $serializedMessage = $this->serializer->serialize($message, 'json', ['groups' => ['messages:read']]);

        // Send the message to the receiver if connected
        foreach ($this->clients as $client) {
            $clientUser = $this->clients[$client]['user'] ?? null;
            if ($clientUser && $clientUser->getId() === $receiver->getId()) {
                $client->send(json_encode([
                    'type' => 'conversation.message.added',
                    'data' => $serializedMessage
                ]));
            }
        }


        $from->send(json_encode([
            'type' => 'conversation.message.added',
            'data' => $serializedMessage
        ]));
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Remove the connection from the clients storage
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        // Close the connection on error
        $conn->close();
    }
}
