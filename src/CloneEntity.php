<?php
declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\entity\Human;
use pocketmine\math\Vector3;

class CloneEntity extends Human {

    private ?Vector3 $lastPos = null;
    private float $smoothForward = 0.4;

    public float $customScale = 1.0;

    public function onUpdate(int $currentTick): bool {
        $hasUpdate = parent::onUpdate($currentTick);

        $currentPos = $this->getPosition();
        $plugin = $this->getWorld()->getServer()->getPluginManager()->getPlugin("resize");

        if(!isset($plugin->players[$this->getNameTag()])) return $hasUpdate;
        $player = $plugin->players[$this->getNameTag()];
        if ($this !== $plugin->clones[$player->getNameTag()]) return $hasUpdate;

        $yaw = deg2rad($this->getLocation()->getYaw());
        $dirX = -sin($yaw);
        $dirZ =  cos($yaw);
        $mountY = $currentPos->y + $this->getEyeHeight();

        $mount = $plugin->mounts[$player->getName()] ?? null;
        if($mount !== null && !$mount->isClosed()){
            $speed = 0.0;
            if($this->lastPos !== null){
                $dx = $currentPos->x - $this->lastPos->x;
                $dz = $currentPos->z - $this->lastPos->z;
                $speed = sqrt($dx * $dx + $dz * $dz);
            }

            $targetForward = 0.4 + min(0.5, $speed * 12.0);
            $this->smoothForward = $this->smoothForward * 0.80 + $targetForward * 0.20;

            // Reculer tant qu'un des points autour de la camera est dans un bloc solide.
            // On teste 9 points en cercle autour de la position cible pour couvrir
            // tous les cas diagonaux (coins de blocs, deplacements obliques, etc).
            $forward = $this->smoothForward;
            $r = 0.12;
            $circle = [
                [0, 0],
                [$r, 0], [-$r, 0], [0, $r], [0, -$r],
                [$r, $r], [-$r, $r], [$r, -$r], [-$r, -$r],
            ];

            while($forward > 0){
                $mx = $currentPos->x + $dirX * $forward;
                $mz = $currentPos->z + $dirZ * $forward;

                $blocked = false;
                foreach($circle as [$ox, $oz]){
                    if($this->getWorld()->getBlockAt(
                        (int)floor($mx + $ox),
                        (int)floor($mountY),
                        (int)floor($mz + $oz)
                    )->isSolid()){
                        $blocked = true;
                        break;
                    }
                }
                if(!$blocked) break;
                $forward -= 0.05;
            }
            $forward = max(0.0, $forward);

            $mount->teleport(new Vector3(
                $currentPos->x + $dirX * $forward,
                $mountY,
                $currentPos->z + $dirZ * $forward
            ));
        }

        $this->lastPos = $currentPos->asVector3();
        return $hasUpdate;
    }
}
