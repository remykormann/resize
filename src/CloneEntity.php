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

        /*if ($this->lastPos !== null) {
            if (!$currentPos->equals($this->lastPos)) {
                // 👉 ici = "event entity move (physique)"
                $this->onPhysicalMove($plugin, $this->lastPos, $currentPos);
            }
        }*/

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

    protected function onPhysicalMove(Main $plugin, Vector3 $from, Vector3 $to): void { 

        $dy = $to->y - $from->y;

        
        /*if ($motion->y == 0 && $dy == 0) {
            $posPlayer = $player->getPosition();
            $diff = $posPlayer->y - ($this->getEyePos()->y - $player->getEyeHeight());
            if($diff > 0.1 || $diff < -0.1){
                //synchronisation y entre clone et joueur
                $player->teleport(new Vector3($posPlayer->x, $this->getEyePos()->y - $player->getEyeHeight(), $posPlayer->z));
            }
        }*/

    }

}
