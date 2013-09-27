<?php

namespace Sabre\DAV\Auth\Backend;

class IMAP extends AbstractBasic {
	protected $imap_server;
	protected $pdo;

	public function __construct($imap_server, $pdo) {
		$this->imap_server = $imap_server;
		$this->pdo = $pdo;
	}

	private function checkUser($username, $password) {
		try {
			$imap = imap_open($this->imap_server, $username, $password, OP_HALFOPEN);
		} catch (Exception $e) {
			return false;
		}
		imap_close($imap);
		return true;
	}

	private function checkUserFromCache($username, $digest) {
		$stmt = $this->pdo->prepare('SELECT username FROM users WHERE username = ? AND digesta1 = ?');
		$stmt->execute(array($username, $digest));
		$result = $stmt->fetchAll();
		if (count($result) === 1)
			return true;
		return false;
	}

	private function updateUser($username, $digest) {
		$stmt = $this->pdo->prepare('REPLACE INTO users (username, digesta1) VALUES (?, ?)');
		$stmt->execute(array($username, $digest));
	}

	private function existsUser($username) {
		$stmt = $this->pdo->prepare('SELECT username FROM users WHERE username = ?');
		$stmt->execute(array($username));
		$result = $stmt->fetchAll();
		if (count($result) === 1)
			return true;
		return false;
	}

	private function createUser($username) {
		$this->pdo->prepare('
			INSERT INTO users (username) VALUES
			("' . $username . '");
		')->execute();
		$this->pdo->prepare('
			INSERT INTO principals (uri, displayname) VALUES
			("principals/' . $username . '", ". $username .");
		')->execute();
		$this->pdo->prepare('
			INSERT INTO calendars (principaluri, displayname, uri, description, components, ctag, transparent) VALUES
			("principals/' . $username . '", "Calendar", "default", "", "VEVENT,VTODO", "1", "0");
		')->execute();
		$this->pdo->prepare('
			INSERT INTO addressbooks (principaluri, displayname, uri, description, ctag) VALUES
			("principals/' . $username . '", "Contacts", "default", "", "1");
		')->execute();
	}

	protected function validateUserPass($username, $password) {
		$username = strtolower($username);
		$digest = hash("sha256", "$username:$password");

		if ($this->checkUserFromCache($username, $digest))
			return true;
		if ($this->checkUser($username, $password)) {
			if (!$this->existsUser($username))
				$this->createUser($username);
			$this->updateUser($username, $digest);
			return true;
		}
		return false;
	}
}

?>
