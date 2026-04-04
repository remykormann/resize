<?php
declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\entity\Human;
use pocketmine\math\Vector3;

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

        // Deplacer le mount (armor stand) a la position des yeux du clone,
        // legerement en avant pour que la camera soit toujours devant le clone.
        $mount = $plugin->mounts[$player->getName()] ?? null;
        if($mount !== null && !$mount->isClosed()){
            $yaw = deg2rad($this->getLocation()->getYaw());
            $forward = 0.9;
            $mount->teleport(new Vector3(
                $this->getPosition()->x - sin($yaw) * $forward,
                $this->getPosition()->y + $this->getEyeHeight(),
                $this->getPosition()->z + cos($yaw) * $forward
            ));
        }

        $this->lastPos = $currentPos->asVector3();
        return $hasUpdate;
    }
}
