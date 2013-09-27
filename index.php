<?php

$db_name = 'sabredav';
$db_user = 'sabredav';
$db_password = 'XXX';
$imap_server = '{mail.host.com/tls}';


require_once 'SabreDAV/vendor/autoload.php';
require_once 'overlay/Sabre/DAV/Auth/Backend/IMAP.php';

// Mapping PHP errors to exceptions
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
	throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}
set_error_handler("exception_error_handler");

$pdo = new \PDO('mysql:host=127.0.0.1;dbname=' . $db_name, $db_user, $db_password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$authBackend = new \Sabre\DAV\Auth\Backend\IMAP($imap_server, $pdo);
$principalBackend = new \Sabre\DAVACL\PrincipalBackend\PDO($pdo);
$caldavBackend = new Sabre\CalDAV\Backend\PDO($pdo);
$carddavBackend = new Sabre\CardDAV\Backend\PDO($pdo);

$tree = array(
	new \Sabre\CalDAV\Principal\Collection($principalBackend),
	new \Sabre\CalDAV\CalendarRootNode($principalBackend, $caldavBackend),
	new \Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
);

$server = new \Sabre\DAV\Server($tree);
$server->addPlugin(new Sabre\DAV\Auth\Plugin($authBackend,'SabreDAV'));
$server->addPlugin(new Sabre\DAV\Browser\Plugin());
$server->addPlugin(new Sabre\DAVACL\Plugin());
$server->addPlugin(new Sabre\CalDAV\Plugin());
$server->addPlugin(new Sabre\CardDAV\Plugin());
$server->exec();

?>
