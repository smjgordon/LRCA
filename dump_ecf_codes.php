<?php
require_once 'private_php/m_player.php';
header('Content-Type: text/plain; charset=UTF-8');

$allPlayers = Player::loadAll();
foreach ($allPlayers as $player) {
	if ($player->ecfGradingCode) echo $player->ecfGradingCode, "\n";
}
?>