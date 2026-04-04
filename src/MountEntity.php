<?php

declare(strict_types=1);

namespace mysonied\resize;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class MountEntity extends Entity {
    
    public static function getNetworkTypeId() : string {
        return EntityIds::ARMOR_STAND;
    }

    protected function getInitialSizeInfo() : EntitySizeInfo {
        return new EntitySizeInfo(0.1, 0.1);
    }

    protected function getInitialDragMultiplier() : float {
        return 0.0;
    }

    protected function getInitialGravity() : float {
        return 0.0;
    }

    public function onUpdate(int $currentTick) : bool {
        return true;
    }
}