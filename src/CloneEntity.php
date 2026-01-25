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

        if(isset($plugin->players[$this->getNameTag()]) === false) return $hasUpdate;
        $player = $plugin->players[$this->getNameTag()];
        if ($this !== $plugin->clones[$player->getNameTag()]) return $hasUpdate;

        $diffX = $player->getPosition()->x - $this->getPosition()->x;
        $diffY = $player->getPosition()->y - ($this->getEyePos()->y - $player->getEyeHeight());
        $diffZ = $player->getPosition()->z - $this->getPosition()->z;

        $motion = $this->getMotion();
        $motionX = 0;
        $motionY = 0;
        $motionZ = 0;
        if( $diffX > 0.01 || $diffX < -0.01) {
            $motionX = -$diffX*0.7;
        }
        if ($diffY > 0.01 || $diffY < -0.01) {
            $motionY = -$diffY*0.7;
        }
        if( $diffZ > 0.01 || $diffZ < -0.01) {
            $motionZ = -$diffZ*0.7;
        }
        if ($motionX != 0 || $motionY != 0 || $motionZ != 0) {
            $player->setMotion(new Vector3($motionX, $motionY, $motionZ));
        }

        $this->lastPos = $currentPos->asVector3();

        return $hasUpdate;
    }

}
