<?php

declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\entity\Location;
use pocketmine\entity\Human;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityFactory;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\attribute\Attribute;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\Skin;
use pocketmine\item\VanillaItems;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;

class Main extends PluginBase{

    //clone of each player resized
    public array $clones = [];

    //player of each clone
    public array $players = [];

    //mount (armor stand) ridden by each player
    public array $mounts = [];

    public function onEnable() : void{
        EntityFactory::getInstance()->register(
            CloneEntity::class,
            function(World $world, CompoundTag $nbt): CloneEntity {

                $skin = new Skin(
                    "clone",
                    str_repeat("\x00", 8192) // skin vide temporaire
                );

                return new CloneEntity(
                    EntityDataHelper::parseLocation($nbt, $world),
                    $skin
                );
            },
            ['CloneEntity']
        );

        EntityFactory::getInstance()->register(MountEntity::class, function(\pocketmine\world\World $world, \pocketmine\nbt\tag\CompoundTag $nbt) : MountEntity {
            return new MountEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['MountEntity']);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("Resize plugin enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "resize":
                if(isset($args[0]) && is_numeric($args[0])){
                    $scale = (float)$args[0];
                    if($scale < 0.1 || $scale > 4.0){
                        $sender->sendMessage("Scale must be greater than 0.1 and less than 4.0");
                        return true;
                    }
                    // Call the resize method here
                    $this->resize($sender, $scale);
                    $sender->sendMessage("You have been resized to scale: " . $scale);
                }
                elseif (isset($args[0]) || $args[0] === "normal") {
                    // Reset the player's size to normal
                    $this->backToNormal($sender);
                }
                else {
                    $sender->sendMessage("Invalid scale value. Please provide a numeric value.");
                }
				return true;
		}
	}

    public function resize(Player $player, float $scale) : void{
            if(isset($this->clones[$player->getName()])){
                $player->teleport($this->clones[$player->getName()]->getPosition());
                $oldClone = $this->clones[$player->getName()];
                $oldClone->close();
                unset($this->clones[$player->getName()]);
                unset($this->players[$oldClone->getNameTag()]);
            }
            if(isset($this->mounts[$player->getName()])){
                $this->mounts[$player->getName()]->close();
                unset($this->mounts[$player->getName()]);
            }

            $location = $player->getLocation();
            $clone = new CloneEntity(
                Location::fromObject($location, $player->getWorld()),
                $player->getSkin()
            );
            if($clone === null) return;
            $clone->setScale($scale);
            $clone->customScale = $scale;
            $clone->teleport(new Vector3($player->getPosition()->x, $player->getPosition()->y, $player->getPosition()->z));
            $clone->setHasGravity(true);
            $clone->setNameTag($player->getNameTag());
            $clone->spawnToAll();
            $name = $player->getName();
            $this->clones[$name] = $clone;
            $cloneName = $clone->getNameTag();
            $this->players[$cloneName] = $player;

            $player->setHasBlockCollision(false);
            $clone->getInventory()->setItemInHand(VanillaItems::DIAMOND_SWORD());
            $player->setInvisible(true);
            $player->setMovementSpeed($scale * $scale * $scale);
            $this->setArmorAndItemClone($clone, $player);

            // Spawn l'ArmorStand invisible a la position des yeux du clone
            $mountY = $clone->getPosition()->y + $clone->getEyeHeight() - 1.62;
            $mountLocation = new Location(
                $clone->getPosition()->x,
                $mountY,
                $clone->getPosition()->z,
                $clone->getWorld(),
                0.0,
                0.0
            );
            $mount = new MountEntity($mountLocation, null);
            $mount->setHasGravity(false);
            $mount->setInvisible(true);
            $mount->spawnTo($player);
            $this->mounts[$name] = $mount;

            // Faire rider le joueur dessus
            $pk = new SetActorLinkPacket();
            $pk->link = new EntityLink(
                $mount->getId(),   // fromActorUniqueId (vehicle)
                $player->getId(),  // toActorUniqueId (rider)
                EntityLink::TYPE_RIDER,
                true,              // immediate
                true,              // causedByRider
                0.0                // vehicleAngularVelocity
            );
            $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function backToNormal(Player $player) : void{
            if(isset($this->clones[$player->getName()]) === false){
                return;
            }
            $clone = $this->clones[$player->getName()];
            $player->teleport($clone->getPosition());
            $clone->close();
            unset($this->clones[$player->getName()]);
            unset($this->players[$clone->getNameTag()]);

            $player->setHasBlockCollision(true);
            $player->setInvisible(false);
            $player->setScale(1.0);
            $player->setMovementSpeed(0.1);

            if(isset($this->mounts[$player->getName()])){
                $pk = new SetActorLinkPacket();
                $pk->link = new EntityLink(
                    $this->mounts[$player->getName()]->getId(),
                    $player->getId(),
                    EntityLink::TYPE_REMOVE,
                    true,
                    false,
                    0.0
                );
                $player->getNetworkSession()->sendDataPacket($pk);
                $this->mounts[$player->getName()]->close();
                unset($this->mounts[$player->getName()]);
            }
    }

    public function setArmorAndItemClone(CloneEntity $clone, Player $player): void {
        // This method can be implemented to sync armor and items between the player and the clone
        $clone->getArmorInventory()->setItem(0, $player->getArmorInventory()->getItem(0)); // helmet
        $clone->getArmorInventory()->setItem(1, $player->getArmorInventory()->getItem(1)); // chestplate
        $clone->getArmorInventory()->setItem(2, $player->getArmorInventory()->getItem(2)); // leggings
        $clone->getArmorInventory()->setItem(3, $player->getArmorInventory()->getItem(3)); // boots
        $clone->getInventory()->setItemInHand($player->getInventory()->getItemInHand());
    }
}
