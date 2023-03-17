Net Provider CHANGELOG
----------------------

22.02.2008 | Net Provider version 1.2.0

FEATURE:   | Správce událostí. Zasílání faktur uživatelům
BUGFIX :   | Networking. Update Mikrotik API, nyní je spouštění příkazů o mnoho rychlejší.

01.02.2008 | Net Provider version 1.1.7

FEATURE:   | IP accounting. Uživatel vidí kolik dat stáhnul ve svém profilu.
IP accounting. Čištění starých dat. Dva měsíce staré záznamy se groupují po měsící. Měsíc staré po dnech.
Person agenda. Přidány údaje pro registraci fyzické osoby. Lepší přehled v hlavním výpisu, možnost vyhledávat podle
všech hlavních údajů uživatele.
Billing. Podpora čtvtletních plateb za internetové služby.
Billing. Podpora půlročních plateb za internetové služby.
Billing. Priorita u plateb. Definuje v jakém pořadí se strhávají platby při placení.
Banking. Manuální upload bankovního výpisu.
Banking. Podpora xml formátu ČSOB.
Banking. Uživatel má možnost vystavení faktury.
Networking. Podpora systému Mikrotik (IP filtr, IP accounting).
Report. Zobrazování čtvrtletních, pololetních, ročních a jednorázových plateb.
BUGFIX :   | Banking. Fatal error při pokusu o zpracování cizího výpisu účtu, opraveno hlášení o chybě.

12.6.2008 | Net Provider version 1.1.6

FEATURE:   | IP accounting. Počitání přenesených dat podle IP adresy.
Nový logovací subsystém, několik úrovní logování.
QoS umí priorizovat provoz i na iGW, které mají několik LAN rozhraní, typicky routery.
BUGFIX :   | Pokud je uživatel vyřazen nebo pasivován, jsou mu deaktivovány služby.

12.05.2008 | Net Provider version 1.1.5

FEATURE:   | Optimalizace IP filtru a qosu. Stačí jen definice jednoho interfacu a to odchozího.
Lokální příkazy se nespouští přes ssh a localhost, ale přes exec příkaz php, což vede k výzaznému urychlení.
BUGFIX :   | Opravena kritická chyba. Při mazání přiřazené platby uživateli se smazala i šablona platby.

01.05.2008 | Net Provider version 1.1.4

BUGFIX :   | Opravena chyba ve zpracování plateb z verze 1.1.3, kdy se v určitých situacích služba neaktivovala

29.04.2008 | Net Provider version 1.1.3

FEATURE:   | Platba má možnost nastavit offset pro datum splatnosti a to individuálně pro každý typ platby.
Platba lze uživateli odebrat. Finanční prostředky se vrátí na uživatelský účet.
Možnost dát uživateli jednorázovou slevu na služby.

08.03.2008 | Net Provider version 1.1.2

FEATURE:   | Přidán odkaz na changelog a uživatelskou dokumentaci v hlavním menu.
Úprava css stylů a celkový design.

BUGFIX:    | Opravena chyba v editaci bankovní položky, kdy indentifikace na "Ignorovat" zkončí chybou.
Opravena chyba v editaci bankovní položky, v IE6 nelze rozdělit platbu více uživatelům.

08.03.2008 | Net Provider version 1.1.1