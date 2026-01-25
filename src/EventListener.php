<?php

declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener {
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(isset($this->plugin->clones[$player->getName()])) {
            $clone = $this->plugin->clones[$player->getName()];
            $clone->close();
            unset($this->plugin->clones[$player->getName()]);
            unset($this->plugin->players[$clone->getNameTag()]);
            $player->teleport($clone->getPosition());
        }
    }

    
    public function onPacket(DataPacketReceiveEvent $event): void {
        $player = $event->getOrigin()->getPlayer();
        if($player === null) return;

        if(isset($this->plugin->clones[$player->getName()]) === false){
            return;
        }
        $clone = $this->plugin->clones[$player->getName()];

        $packet = $event->getPacket();

        if($packet instanceof PlayerAuthInputPacket){
            $fromY = $player->getEyePos()->y;
            $toY = $packet->getPosition()->y;
            $toY = round($toY, 2);
            $bitSet = $packet->getInputFlags();
            $active = [];

            for($i = 0; $i < $bitSet->getLength(); $i++){
                if($bitSet->get($i)){
                    $active[] = $i; 
                }
            }

            $jumpHeight = 0.52;;
            $speed = 0.2;

            if(in_array(PlayerAuthInputFlags::JUMPING, $active) && $clone->isOnGround()){
                // le joueur MONTE
                $clone->setMotion(new Vector3(0, $jumpHeight, 0));
            }

            if(in_array(PlayerAuthInputFlags::LEFT, $active)){
                // le joueur VA A GAUCHE
                $forward = $player->getDirectionVector()->multiply($speed);
                $clone->setMotion(new Vector3($forward->z, $clone->getMotion()->y, -$forward->x));
            }
            if(in_array(PlayerAuthInputFlags::RIGHT, $active)){
                // le joueur VA A DROITE
                $forward = $player->getDirectionVector()->multiply($speed);
                $clone->setMotion(new Vector3(-$forward->z, $clone->getMotion()->y, $forward->x));
            }
            if(in_array(PlayerAuthInputFlags::UP, $active)){
                // le joueur VA EN AVANT
                $forward = $player->getDirectionVector()->multiply($speed);
                $clone->setMotion(new Vector3($forward->x, $clone->getMotion()->y, $forward->z));
            }
            if(in_array(PlayerAuthInputFlags::DOWN, $active)){
                // le joueur VA EN ARRIERE
                $forward = $player->getDirectionVector()->multiply($speed);
                $clone->setMotion(new Vector3(-$forward->x, $clone->getMotion()->y, -$forward->z));
            }
        }
    }
}