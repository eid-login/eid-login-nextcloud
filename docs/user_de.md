# eID-Login Benutzerdokumentation

Dies ist die Dokumentation der eID-Login App für Nextcloud Benutzer.

## Was ist die eID-Login App für Nextcloud?

Die eID-Login App für Nextcloud ermöglicht es Benutzern sich bei ihrem Nextcloud-Konto mit einer elektronischen Identität (eID) statt mit einer Kombination aus Benutzername und Passwort anzumelden.
Dafür kommt ein externer Dienst zum Einsatz, welcher die eID für Nextcloud zugänglich macht.

## Was ist eID und welchen Nutzen bringt der Einsatz?

Der Nutzen von eID für den Login ist vor allem ein Sicherheitsgewinn für die Benutzer der Plattform.
Fragen zum Hintergrund und Nutzen der eID werden auf dem vom Bundesministerium des Innern, für Bau und Heimat bereitgestellten [Personalausweisportal](https://www.personalausweisportal.de/Webs/PA/DE/service/faq/faq-node.html) beantwortet.
Den Sicherheitsmechanismen widmet sich [diese Seite](https://www.personalausweisportal.de/Webs/PA/DE/buergerinnen-und-buerger/sicherheit/sicherheit-node.html) auf dem gleichen Portal.

## Was sind die Voraussetzungen für die Nutzung des eID-Login?

Sie benötigen für die Nutzung der eID-Login Funktion eine eID (z.B. die Online-Ausweisfunktion eines deutschen Personalausweises) sowie die Möglichkeit auf diese mit einem Kartenleser oder Smartphone und einem [eID-Client](https://www.personalausweisportal.de/Webs/PA/DE/buergerinnen-und-buerger/online-ausweisen/software/software-node.html) zuzugreifen.

## Wie kann ich den eID-Login für mein Nextcloud Konto einrichten?

Um sich mit dem eID-Login bei Ihrem Nextcloud Konto anzumelden, muss vorher eine eID mit dem Konto verknüpft werden.
Die Möglichkeit dazu findet sich auf der Seite `Sicherheit` bei den persönlichen `Einstellungen`.
Klicken Sie dort auf den Knopf `Verknüpfung zu eID erstellen`.
Nachdem die Verknüpfung erfolgreich erstellt wurde, können Sie den eID-Login Button auf der Anmeldeseite für den Zugang zu Ihrem Konto nutzen.

## Warum sollte ich die Anmeldung mit Passwort deaktivieren?

Das Deaktivieren der Anmeldung mit Passwort erhöht die Sicherheit Ihres Kontos.
Sollte Ihr Passwort jemals in falsche Hände gelangen, ist Ihr Nextcloud Zugang
trotzdem geschützt.

## Ich habe keinen Zugriff mehr auf meine eID, wie kann ich mich trotzdem anmelden?

Falls Sie keinen Zugriff mehr auf Ihre eID haben, können Sie die `Passwort vergessen?` Funktion von Nextcloud benutzen, um sich ein neues Passwort für Ihr Konto zu vergeben.
Eine Deaktivierung des Anmeldens mit Passwort wird bei diesem Vorgang aufgehoben.

## Wie kann ich die Verknüpfung einer eID mit meinem Konto löschen?

Eine bestehende Verknüpfung Ihres Kontos mit einer eID kann auch wieder gelöscht werden.
Die Möglichkeit dazu findet sich auf der Seite `Sicherheit` bei den persönlichen `Einstellungen`.
Klicken Sie dort auf den Knopf `Verknüpfung zu eID löschen`.

## Welche Informationen mit Bezug zur eID werden in der Nextcloud-Datenbank gespeichert?

In der Standard-Konfiguration der Nextcloud App wird ein mit der eID verbundenes Pseudonym in der Datenbank gespeichert.
Zudem haben Administratoren die Möglichkeit, weitere Attribute der eID, wie z.B. Vor- und Nachnamen, auszulesen und zu speichern.
Bitte wenden Sie sich an den Administrator von Nextcloud um nähere Informationen zu erhalten.
