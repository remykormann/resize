<?php

declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerMissSwingEvent;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\player\Player;

class EventListener implements Listener {
    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if(isset($this->plugin->clones[$player->getName()])) {
            $clone = $this->plugin->clones[$player->getName()];
            $player->teleport($clone->getPosition());
            $clone->close();
            unset($this->plugin->clones[$player->getName()]);
            unset($this->plugin->players[$clone->getNameTag()]);
        }
    }

    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if($entity instanceof Player){
            if(isset($this->plugin->clones[$entity->getName()]) === false){
                return;
            }
            //annuler les degats si le joueur est dans un block
            if(!isset($event->doApply)){
                $event->cancel();
                return;
            }
        }
        if($entity instanceof CloneEntity){
            if(!isset($this->plugin->players[$entity->getNameTag()])) return;
            $player = $this->plugin->players[$entity->getNameTag()];
            $damageEvent = new EntityDamageEvent($player, $event->getCause(), $event->getFinalDamage());
            $damageEvent->doApply = true;
            $player->attack($damageEvent);
            $event->cancel();
        }
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if(isset($this->plugin->clones[$player->getName()]) === false){
            return;
        }
        $clone = $this->plugin->clones[$player->getName()];

        // Synchroniser la rotation du clone avec celle du joueur
        $clone->setRotation($player->getLocation()->getYaw(), $player->getLocation()->getPitch());
    }

    public function onPlayerMissSwing(PlayerMissSwingEvent $event): void {
        $player = $event->getPlayer();
        if(isset($this->plugin->clones[$player->getName()]) === false){
            return;
        }
        $clone = $this->plugin->clones[$player->getName()];

        $pk = new AnimatePacket();
        $pk->action = AnimatePacket::ACTION_SWING_ARM;
        $pk->actorRuntimeId = $clone->getId();

        $clone->getWorld()->broadcastPacketToViewers($clone->getPosition(), $pk);

        $event->cancel();
    }

    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void {
        $player = $event->getPlayer();
        if(isset($this->plugin->clones[$player->getName()]) === false){
            return;
        }
        $clone = $this->plugin->clones[$player->getName()];
        $clone->getInventory()->setItemInHand($event->getItem());
    }

    public function onInventoryChange(InventoryTransactionEvent $event): void {
        $player = $event->getTransaction()->getSource();
        if(isset($this->plugin->clones[$player->getName()]) === false){
            return;
        }
        $clone = $this->plugin->clones[$player->getName()];
        $this->plugin->setArmorAndItemClone($clone, $player);
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

            $jumpHeight = 0.52;
            $speed = 0.3;

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