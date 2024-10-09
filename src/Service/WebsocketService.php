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

    public function onOpen(ConnectionInterface $conn)
    {
        // Attach the new connection to the clients storage
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if(!isset($msg['type'])){
            $from->send(json_encode([
                'type' => 'error',
                'data' => 'Key \'type\'is required'
            ]));
            return;
        }

        if ($msg['type'] === 'conversation.message.created') {
            // Handle authentication
            $this->handleChatMessage($from, $msg['token']);
            return;
        }
        
        echo "Message reçu : " . $msg . PHP_EOL;

         foreach ($this->clients as $client) {
            $client->send($msg);
        }


        // if (!$data) {
        //     $from->send(json_encode(['error' => 'Invalid message format']));
        //     return;
        // }

        // if (isset($data['type']) && $data['type'] === 'auth') {
        //     // Handle authentication
        //     $this->authenticate($from, $data['token']);
        //     return;
        // }

        // // Check if the user is authenticated
        // if (!isset($this->clients[$from]['user'])) {
        //     $from->send(json_encode(['error' => 'Unauthorized']));
        //     return;
        // }

        // Handle incoming chat message
        // $this->handleChatMessage($from, $data);
    }

    // private function authenticate(ConnectionInterface $conn, string $token): void
    // {
    //     try {
    //         // Decode the JWT token to get the payload
    //         $payload = $this->jwtEncoder->decode($token);
    //         $email = $payload['username'] ?? null;

    //         if (!$email) {
    //             $conn->send(json_encode(['error' => 'Invalid token payload']));
    //             $conn->close();
    //             return;
    //         }

    //         // Retrieve the user from the database
    //         $user = $this->userRepository->findOneBy(['email' => $email]);

    //         if (!$user) {
    //             $conn->send(json_encode(['error' => 'User not found']));
    //             $conn->close();
    //             return;
    //         }

    //         // Store the user in the clients storage associated with the connection
    //         $this->clients[$conn] = ['user' => $user];

    //         $conn->send(json_encode(['success' => 'Authenticated']));
    //     } catch (\Exception $e) {
    //         $conn->send(json_encode(['error' => 'Authentication failed']));
    //         $conn->close();
    //     }
    // }

    private function handleChatMessage(ConnectionInterface $from, string $data): void
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
        // foreach ($this->clients as $client) {
        //     $clientUser = $this->clients[$client]['user'] ?? null;
        //     if ($clientUser && $clientUser->getId() === $receiver->getId()) {
        //         $client->send($serializedMessage);
        //     }
        // }


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
