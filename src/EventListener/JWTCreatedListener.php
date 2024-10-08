<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class JWTCreatedListener
{
    #[AsEventListener(event: 'JWTCreated')]
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if(!$user instanceof User){
            return;
        }

        $data["lastname"] = $user->getLastname();
        $data["firstname"] = $user->getFirstname();

        $event->setData($data);
    }
}
