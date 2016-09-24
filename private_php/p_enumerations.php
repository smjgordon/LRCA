<?php
// TODO: refactor
abstract class Season {
	const Winter = 1;
	const Summer = 2;
	const BothDefaultWinter = 3;
	const BothDefaultSummer = 4;
}

abstract class MatchStatus {
	const Unplayed = 0;
	const Played = 1;
	const Postponed = 2;
	const Defaulted = 3;
	const ScoredBye = 4;
	const Void = 5;
}

abstract class MatchStyle {
	const Standard = 1;
	const RapidSame = 2;
	const RapidDifferent = 3;
}

abstract class Colours {
	const HomeWhiteOnOdds = 1;
	const HomeBlackOnOdds = 2;
	const DecidePerMatch = 3;
}

abstract class PlayerID {
	const Unknown = 1;
	const BoardDefault = 2;
}

abstract class BoardDefaultReason {
	const Standard = 1;
	const IllegalBoardOrder = 2;
}

abstract class SessionStatus {
	const Failed = 0;
	const Active = 1;
	const Terminated = 2;
	const Expired = 3;
	const Superseded = 4;
}

abstract class UserStatus {
	const Inactive = 0;
	const Active = 1;
	const PendingPassword = 2;
}

abstract class PasswordResetKeyStatus {
	const Unused = 0;
	const Used = 1;
	const Expired = 2;
	const Superseded = 3;
}

// TODO: move back once p_division.php has been done away with
abstract class DivisionFormat {
	const RoundRobinSingle = 1;
	const RoundRobinDouble = 2;
	const Swiss = 11;
	const Knockout = 21;
}

abstract class Breakdown {
	const ByMonth = 1;
	const ByRound = 2;
}

abstract class GameResult {
	const DoubleDefault = -2;
	const AwayWin = -1;
	const Draw = 0;
	const HomeWin = 1;
}
?>