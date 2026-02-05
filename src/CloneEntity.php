<?php
declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\entity\Human;
use pocketmine\event\entity\EntityEvent;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\World;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\block\VanillaBlocks;


class CloneEntity extends Human {

    private ?Vector3 $lastPos = null;

    public float $customScale = 1.0;

    public function onUpdate(int $currentTick): bool {
        $hasUpdate = parent::onUpdate($currentTick);

        $currentPos = $this->getPosition();
        $plugin = $this->getWorld()->getServer()->getPluginManager()->getPlugin("resize");

        if(!isset($plugin->players[$this->getNameTag()])) return $hasUpdate;
        $player = $plugin->players[$this->getNameTag()];
        if ($this !== $plugin->clones[$player->getNameTag()]) return $hasUpdate;

        // ==========

        $loc = $this->getLocation();
        $yaw = deg2rad($loc->getYaw());
        $pitchDeg = $loc->getPitch();
        $pitchDeg = max(-60, min(60, $pitchDeg));
        $pitch = deg2rad($pitchDeg);


        $dirX = -cos($pitch) * sin($yaw);
        $dirY = -sin($pitch);
        $dirZ =  cos($pitch) * cos($yaw);

        // hauteur réelle du clone
        $height = $this->getSize()->getHeight();

        // tête visuelle du clone
        $headPos = $this->getPosition()->add(0, $height * 0.9, 0);

        // distance devant la tête proportionnelle à la taille
        $h1 = 0.1;
        $h2 = 0.3;

        $k1 = 0.9;
        $k2 = 0.75;

        $t = log($height / $h1) / log($h2 / $h1);
        $k = $k1 + ($k2 - $k1) * $t;

        $distance = $k * $height;

        $targetPos = $headPos->add(
            $dirX * $distance,
            $dirY * $distance,
            $dirZ * $distance
        );

        //return if there is a block at the position
        $block = $this->getWorld()->getBlockAt(
            (int)floor($targetPos->x),
            (int)floor($targetPos->y),
            (int)floor($targetPos->z)
        );
        if ($block->isSolid()) {
            $targetPos = $headPos->add(
                - $dirX * 0.2 * $distance,
                - $dirY * 0.2 * $distance,
                - $dirZ * 0.2 * $distance
            );  
        }

        /*if($player->getScale() < 0.5){
            $blockEye = $player->getWorld()->getBlockAt(
                (int)floor($player->getPosition()->x),
                (int)floor($player->getPosition()->y + 1.62),
                (int)floor($player->getPosition()->z)
            );
            if ($blockEye->isSolid()) {
                $blockPos = new BlockPosition(
                    (int)$player->getPosition()->x,
                    (int)$player->getPosition()->y,
                    (int)$player->getPosition()->z
                );
                $pk = UpdateBlockPacket::create(
                    $blockPos,
                    VanillaBlocks::BARRIER()->getStateId(),
                    0,
                    0
                );

                $player->getNetworkSession()->sendDataPacket($pk); 
            }
        }*/

        // ==========

        $diffX = $player->getPosition()->x - $targetPos->x;
        $diffY = $player->getPosition()->y + 1.62 - $targetPos->y;
        $diffZ = $player->getPosition()->z - $targetPos->z;

        $motionX = 0;
        $motionY = 0;
        $motionZ = 0;

        if ($diffX > 0.01 || $diffX < -0.01) {
            $motionX = -$diffX * 0.7;
        }
        if ($diffY > 0.01 || $diffY < -0.01) {
            $motionY = -$diffY * 0.6;
        }
        if ($diffZ > 0.01 || $diffZ < -0.01) {
            $motionZ = -$diffZ * 0.7;
        }

        if ($motionX != 0 || $motionY != 0 || $motionZ != 0) {
            $player->setMotion(new Vector3($motionX, $motionY, $motionZ));
        }

        $this->lastPos = $currentPos->asVector3();
        return $hasUpdate;
    }


}
