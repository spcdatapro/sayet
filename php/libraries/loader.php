<?php

require_once 'php-mt940/src/Parser/Banking.php';
require_once 'php-mt940/src/Parser/Banking/Mt940.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Abn.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Asn.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Bunq.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Hsbc.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Ing.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Kbs.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Knab.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Kontist.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Penta.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Rabo.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Sns.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Spk.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Triodos.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Unknown.php';
require_once 'php-mt940/src/Parser/Banking/Mt940/Engine/Zetb.php';
require_once 'php-mt940/src/Banking/Statement.php';
require_once 'php-mt940/src/Banking/Transaction.php';
require_once 'php-mt940/src/Banking/Transaction/Type.php';
require_once 'php-mt940/src/Banking/Hsbc/HsbcTransaction.php';