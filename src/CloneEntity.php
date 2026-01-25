<?php
declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\entity\Human;
use pocketmine\event\entity\EntityEvent;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\world\World;

class CloneEntity extends Human {

    private ?Vector3 $lastPos = null;

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
        $pitch = deg2rad($loc->getPitch());

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

        // ==========

        $diffX = $player->getPosition()->x - $targetPos->x;
        $diffY = $player->getPosition()->y - ($targetPos->y - $player->getSize()->getHeight());
        $diffZ = $player->getPosition()->z - $targetPos->z;

        $motionX = 0;
        $motionY = 0;
        $motionZ = 0;

        if ($diffX > 0.01 || $diffX < -0.01) {
            $motionX = -$diffX * 0.7;
        }
        if ($diffY > 0.01 || $diffY < -0.01) {
            $motionY = -$diffY * 0.7;
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
