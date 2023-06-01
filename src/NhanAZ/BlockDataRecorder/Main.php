<?php

declare(strict_types=1);

namespace NhanAZ\BlockDataRecorder;

use NhanAZ\BlockData\BlockData;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

	protected BlockData $blockData;

	protected function onEnable(): void {
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->blockData = new BlockData($this, false);
	}

	private function jsonStringToItem(string $string): Item {
		$itemDecoded = json_decode($string, true);
		$itemDeserialize = Item::legacyJsonDeserialize($itemDecoded);
		return $itemDeserialize;
	}

	public function onBlockPlace(BlockPlaceEvent $event): void {
		$item = $event->getItem();
		$blocks = $event->getTransaction()->getBlocks();
		foreach ($blocks as [$x, $y, $z, $block]) {
			if ($item instanceof ItemBlock) {
				$itemSerialize = $item->jsonSerialize();
				$itemEncoded = json_encode($itemSerialize);
				if (!preg_match('/^\{"id":\d+,"count":\d+\}$/', $itemEncoded)) {
					$this->blockData->setData($block, $itemEncoded);
				}
			}
			break;
		}
	}

	public function onBlockBreak(BlockBreakEvent $event): void {
		if (!$event->isCancelled()) {
			$block = $event->getBlock();
			$blockData = $this->blockData->getData($block);
			if ($blockData !== null) {
				$itemDrop = $this->jsonStringToItem($blockData);
				if ($itemDrop instanceof ItemBlock) {
					$itemDrop->setCount(1);
					$event->setDrops([$itemDrop]);
				}
			}
		}
	}
}
