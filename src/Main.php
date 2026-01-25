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

class Main extends PluginBase{

    //clone of each player resized
    public array $clones = [];

    //player of each clone
    public array $players = [];

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

            $location = $player->getLocation();
            $clone = new CloneEntity(
                Location::fromObject($location, $player->getWorld()),
                $player->getSkin()
            );
            if($clone === null) return;
            $ratio = $scale;
            $clone->setScale($ratio);
            $clone->teleport(new Vector3($player->getPosition()->x, $player->getPosition()->y, $player->getPosition()->z));
            $clone->setHasGravity(true);
            $clone->setNameTag($player->getNameTag());
            $clone->spawnToAll();
            $name = $player->getName();
            $this->clones[$name] = $clone;
            $cloneName = $clone->getNameTag();
            $this->players[$cloneName] = $player;

            $posX = $clone->getPosition()->x;
            $posY = $clone->getEyePos()->y - $player->getEyeHeight();
            $posZ = $clone->getPosition()->z;

            $player->setHasBlockCollision(false);
            $player->setInvisible(true);
            $player->teleport(new Vector3($posX, $posY, $posZ));
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
    }
}
