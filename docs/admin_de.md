# eID-Login Administratordokumentation

Dies ist die Administrator Dokumentation der quelloffenen, kostenfreien eID-Login App für die Nextcloud Plattform.

## Was ist die eID-Login App für Nextcloud?

Die eID-Login App ermöglicht Benutzern den Zugang zu ihrem Nextcloud-Konto mit einer elektronischen Identität (eID) statt mit einer Kombination aus Benutzername und Passwort.

## Was ist eID und welchen Nutzen bringt der Einsatz?

Der Nutzen von eID für den Login ist vor allem ein Sicherheitsgewinn für die Benutzer der Plattform.
Fragen zum Hintergrund und Nutzen der eID werden auf dem vom Bundesministerium des Innern, für Bau und Heimat bereitgestellten [Personalausweisportal](https://www.personalausweisportal.de/Webs/PA/DE/service/faq/faq-node.html) beantwortet.
Den Sicherheitsmechanismen widmet sich [diese Seite](https://www.personalausweisportal.de/Webs/PA/DE/buergerinnen-und-buerger/sicherheit/sicherheit-node.html) auf dem gleichen Portal.

## Ist beim eID-Login ein weiteres System neben Nextcloud beteiligt?

Damit Nextcloud die eID zugänglich gemacht werden kann, kommt ein externer Dienst zum Einsatz.
Dieser `Identity Provider` genannte Dienst kümmert sich um die Authentifizierung von Benutzern.
Die eID-Login App greift auf Informationen des `Identity Providers` zu, um Benutzer einzuloggen und mit entsprechenden Rechten auszustatten.

## Welche Arten von Identity Providern werden unterstützt?

Es können alle `Identity Provider` angebunden werden, welche das SAML Protokoll nutzen, um mit Nextcloud zu kommunizieren.
Darüber hinaus ist die Nutzung von Diensten möglich, die einen eID-Server gemäß [BSI TR-03130](https://www.bsi.bund.de/DE/Themen/Unternehmen-und-Organisationen/Standards-und-Zertifizierung/Technische-Richtlinien/TR-nach-Thema-sortiert/tr03130/TR-03130_node.html) zur Verfügung stellen, welcher auch auf SAML basiert.
Die einfachste Art der Anbindung ist die Nutzung des [SkIDentity Service](https://www.skidentity.de/), welcher bereits für die unkomplizierte Nutzung vorkonfiguriert ist.

## Was ist das SAML Protokoll?

Die Security Assertion Markup Language (SAML) ist ein offener, weit verbreiteter Standard zum Austausch von Authentifizierungs- und Autorisierungsinformationen.
Damit Nextcloud Teil einer solchen Kommunikation sein kann, stellt es Informationen zu seiner Rolle und seinen Eigenschaften unter der SAML Metadaten URL bereit.
Weitere Informationen zu SAML finden sich bei [OASIS](https://wiki.oasis-open.org/security/FrontPage).

## Gibt es für die Anbindung gemäß BSI TR-03130 etwas zu beachten?

Kommt eine Anbindung gemäß BSI TR-03130 in Betracht, so sind einige Dinge zu beachten.
Es muss in den Einstellungen der App eine Konfiguration des `AuthnRequestExtension` XML-Elementes vorgenommen werden.
Dieses legt fest, welche Informationen durch Nextcloud beim `Identity Provider` abgefragt werden.
Bitte wenden Sie sich an den Betreiber des `Identity Providers`, um genauere Informationen zu erhalten.

Wegen der Nutzung von `SAML-Redirect Binding` beim Verarbeiten der Authentifizierungsantwort erfolgt der Aufruf einer sehr langen URL, die von den typischen Webservern nicht in der Standardkonfiguration verarbeitet werden kann.
Es muss daher die Webserver Konfiguration angepasst werden um das Auftreten eines HTTP 414 Fehler zu vermeiden:
* [Apache](http://httpd.apache.org/docs/2.4/en/mod/core.html#limitrequestline)
* [NGINX](https://nginx.org/en/docs/http/ngx_http_core_module.html#large_client_header_buffers)

## Welche Informationen werden vom Identity Provider für Nextcloud bereitgestellt?

Grundlage für die Verknüpfung zwischen einem Nextcloud-Konto und einer eID ist der `eIdentifier`.
Dabei handelt es sich je nach Anbieter um ein abgeleitetes oder in der eID enthaltenes Datum, welches einer eID eindeutig zuzuordnen ist.
Zudem können je nach Berechtigung des `Identity Providers` weitere Attribute der eID von Nextcloud abgefragt werden.
Diese Attribute werden in der Datenbank abgelegt und können dann in Nextcloud genutzt werden.
Für weitere Informationen bzgl. der möglichen Attribute und der zur Abfrage benötigten Konfiguration wenden Sie sich bitte an den Betreiber des jeweiligen `Identity Providers`.

## Was kostet die Nutzung eines Identity Providers?

Die Kosten für die Nutzung eines Identity Provider ist vom jeweiligen Anbieter abhängig.
Die Nutzung des SkIDentity Dienstes ist im Zusammenhang mit der eID-Login App kostenfrei, falls nur der `eIdentifier` abgefragt wird.

## Welche Systemanforderungen gibt es für die Nutzung des eID-Login?

- Nextcloud-Version: >= 19
- PHP-Version: >= 7.3
- PHP-Module: openssl (in der Regel auch bei günstigen Anbietern vorhanden)
- Datenbank: PostgreSQL (>= 11.9), MySQL (>= 8) oder MariaDB (>= 10.3)
- Korrekt eingerichtetes E-Mail Setup für Benachrichtigung von Administratoren
- Korrekt eingerichtetes Cronjob Setup für Hintergrundaufgaben
- Einsatz von SSL

## Wie erfolgt die Installation und Einrichtung?

Die Installation der App erfolgt aus dem App-Store nach dem Standard Vorgehen von Nextcloud.
Nachdem die App installiert wurde ist für ihre Einrichtung eine eigene Seite unter `Einstellungen` verfügbar.
Bei der ersten Einrichtung führt Sie ein Wizard durch die notwendigen Schritte.

## Wie gehe ich vor, um den eID-Login mit SkIDentity nutzen zu können?
Im folgenden Screencast sehen Sie die notwendigen Schritte für die Einrichtung des eID-Login für Nextcloud mit SkIDenttiy:
- Nutzung des Wizard
- Registrierung bei SkIDentity
- Erstellung einer [Cloud Identität](https://www.skidentity.de/help/faq1/#faq2)
- Einrichtung von Nextcloud als Service Provider bei SkIDentity
- Verknüpfung der eID mit dem Nextcloud Benutzerkonto
- eID-Login mittels der erstellten Cloud Identität

## Beim Versuch den Wizard zu nutzen kommen Fehlermeldungen, die ich nicht verstehe. Was kann ich tun?

Falls es im Wizard zu genrellen Fehlermeldungen kommt wie z.B. 'Identity Provider Einstellungen konnten nicht geladen werden' oder 'Einstellungen konnten nicht gespeichert werden' dann kann es sein dass die SSL Konfiguration von Nextcloud nicht korrekt ist. Vielleicht hilft folgende Konfigurationseinstellung in Nextcloud:
- 'overwriteprotocol' => 'https'

Weitere Informationen dazu finden Sie in der [Nextcloud Dokumentation](https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/reverse_proxy_configuration.html#overwrite-parameters).

## Was bedeutet die Option 'Verschlüsselung von Authentifizierungsantworten erzwingen'?
Das SAML Protokoll sieht die Möglichkeit vor, Authentifizierungsantworten des Identity Provider nur verschlsselt zu übertragen. Im Falle einer Anbindung gemäß TR-03130 ist dies z.B. erforderlich. Da die Kommunikation mit dem Identity Provider jedoch schon über die Transport-Verschlüsselung abgesichert ist bieten viele Identity Provider diese Option nicht an. Stellen Sie also vorher sicher, dass der verwendete Identity Provider diese Option unterstützt. Nähere Informationen erhalten Sie vom jeweiligen Betreiber des Identity Providers.

## Wie können die Einstellungen später angepasst werden?

Nach der Einrichtung sind unter der App spezifischen Seite bei `Einstellungen` die Einstellungen der App direkt einsehbar und können dort manuell geändert werden.

## Was hat es mit den Zertifikaten und deren Wechsel auf sich?

Für die Kommunikation mit dem `Identity Provider` werden Zertifikate benötigt.
Je nach Anbindung kommen diese für die Signatur und gegebenenfalls auch für die Verschlüsselung von Nachrichten zum Einsatz.
Die Zertifikate werden bei der Einrichtung automatisch mit einer Gültigkeit von zwei Jahren erstellt.
Eine im Hintergrund laufende Aufgabe prüft diese Zertifikate regelmäßig auf Ihre Gültigkeit.
Vor Ende der Gültigkeit werden von dieser Hintergrundaufgabe zwei Aktionen durchgeführt:
* Zwei Monate vor Ende der Gültigkeit werden neue Zertifikate erstellt und diese über die Publikation in den SAML Metadaten für die Nutzung vorbereitet. Der `Identity Provider` muss je nach Anbieter gegebenenfalls noch vom Administrator über die Existenz der neuen Zertifikate informiert werden, falls diese nicht aus den SAML Metadaten von Nextcloud importiert werden.
* Einen Monat vor Ende der Gültigkeit werden die bestehenden Zertifikate in der Datenbank gesichert und die vorbereiteten Zertifikate werden von nun an genutzt.

Alle Nextcloud Administratoren werden über diese Schritte per E-Mail informiert.

Falls ein Wechsel der Zertifikate zu einem früheren Zeitpunkt geschehen soll, so ist dies über die Einstellungen der App möglich.

## Wie kann der Wizard zur Einrichtung erneut durchlaufen werden?

Wenn die Einstellungen zurückgesetzt werden, so ist der Wizard wieder auf der App spezifischen Seite unter `Einstellungen` verfügbar.

## Welche Daten werden in der Datenbank abgelegt?

Die eID-Login App legt ihre Einstellungen in der Tabelle `oc_appconfig` ab.
Zudem werden für die Speicherung der eID spezifischen Daten und Daten, welche während des Betriebs anfallen, folgende Tabellen erzeugt:
 * oc_eidlogin_eid_attributes
 * oc_eidlogin_eid_continuedata
 * oc_eidlogin_eid_responsedata
 * oc_eidlogin_eid_users

## Wie erfahren die Benutzer von der Möglichkeit zum eID-Login?

Nach der Einrichtung der App wird ein eID-Login Button auf der Anmeldeseite angezeigt.
Zudem wird für alle bestehenden Benutzer eine Benachrichtigung über die Möglichkeit zum eID-Login hinterlegt.
Diese beinhaltet einen Link direkt zu den persönlichen Einstellungen für Sicherheit, wo ein Benutzer sein Konto mit einer eID verknüpfen kann.
Nähere Informationen dazu finden sich in der Benutzerdokumentation der eID-Login App.
Die Benachrichtigung wird auch für alle neu angelegten Benutzer hinterlegt, solange die App installiert und eingerichtet ist.
