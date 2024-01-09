***
**Autor:** Manuel Fellner
**Version:** 09.01.2024

## 1. Einführung

Wir haben folgende Aufgabenstellung:

>Anbei findet ihr eine (nicht sehr sichere PHP-Webanwendung), die ihr mit `docker-compose up` starten koennt; danach steht sie euch auf [https://localhost:8042](https://localhost:8042) zur Verfuegung. Deine Aufgabe ist es nun, die Anwendung gegen dir bekannte Angriffe wie folgt abzusichern:


## 2. Aufsetzen der Webanwendung lokal

Als erstes gehen wir sicher, dass die Webanwendung erstmals lokal auf unserem Rechner läuft, dafür laden wir uns die `.zip` Datei runter, extrahieren sie, und führen den folgenden Befehl aus:
```shell
$ docker compose up -d
```

*(wir müssen dafür docker bzw. docker compose installiert haben)*

Danach begrüßt uns auf `localhost:8042` folgende Anwendung:

![](https://uploads.mfellner.com/eT5Tm422dNWr.png)



## 2. Authentifizierung

Aufgabenstellung:
>Aendere die Anwendung so ab, dass das Passwort nicht im Klartext in der Datenbank gespeichert wird, sondern (stark) gehasht wird (zum Beispiel mittels `password_hash` [1]). Achte darauf, dass keine User Enumeration Angriffe durch Messen der Antwortzeit moeglich sind.


### 2.1 Passwörter verschlüsseln

Als erstes beschäftigen wir uns damit, dass die Passwörter in der Datenbank verschlüsselt gespeichert werden.
Dazu verwenden wir die `password_hash()` methode (https://www.php.net/manual/en/function.password-hash.php) mit dem `PASSWORD_DEFAULT` Algorithmus.

Das sieht dann beim `register.php` folgendermaßen aus:

```php
$password = password_hash($_POST['password'],  PASSWORD_DEFAULT);
```

Beim `login.php` müsssen wir jetzt aber beim Passwortvergleich aufpassen, da wir jetzt ja ein hash haben.

Jedoch stellt uns PHP für den Vergleich ebenso eine Methode zur Verfügung: `password_verify` (https://www.php.net/manual/en/function.password-verify).

```php
// secure comparison with password_verify  
if (!password_verify($password, $storedPassword)) {  
    header("Location: login.html");  
    exit();  
}
```


![](https://uploads.mfellner.com/90yNnpl4pNnL.png)

### 2.2 Gegen Enumeration Attacken schützen

Enumeration Attacken sind Attacken, welche darauf abzielen, zu schauen, ob ein gewisser Benutzername bei der Applikation existiert. 
Dies kann passieren, wenn man z.B. das Passwort eines Benutzers erst aus der Datenbank holt, wenn der Benutzername stimmt.
Das bedeutet, dass die Antwortzeit der Website bei den folgenden zwei Szenarien GLEICH sein muss:
1. Benutzer gibt falschen Benutzernamen und falsches Passwort ein
2. Benutzer gibt richtigen Benutzernamen und falsches Passwort ein

Dies können wir einfach lösen, indem wir die Applikation künstlich langsamer machen (also "schlafen legen").
Die `sleep()` Methode (https://www.php.net/manual/en/function.sleep) gibt uns die Möglichkeit, den Ablauf der App um beliebig viel Sekunden zu verzögern.

Also bauen wir bei den zwei oben genannten Use-Cases eine künstliche Verzögerung ein:

```php
// if the username is incorrect  
if ($result->num_rows === 0) {  
    header("Location: login.html");  
    sleep(1);  
    exit();  
}

//...
// if the username is correct -> secure password comparison  
if (!password_verify($password, $storedPassword)) {  
    header("Location: login.html");  
    sleep(1);  
    exit();  
}
```

Nun ist die Antwortzeit der App bei einem inkorrekten Benutzernamen die folgende:

![](https://uploads.mfellner.com/kllVPxaAJsvD.png)

Ebenso ist die Antwortzeit der App bei einem korrekten Benutzernamen die folgende:

![](https://uploads.mfellner.com/IoDxWom7WGz4.png)


## 3. Session Handling

Aufgabenstellung:
>Stelle sicher, dass das Session Cookie nicht durch JavaScript-Code abgerufen werden kann (Cookie Flag `HTTPOnly`) sowie bei CSRF-Angriffen nicht mitgeschickt wird (Cookie Flag `SameSite`).


### 3.1 HTTPOnly cookie setzen

Um zu verhindern, dass unser `PHPSESSID` von JavaScript code ausgelesen wird, könnten wir den `HttpOnly` Cokie auf `true` setzen.
 Dies können wir mit einem zentralen Konfigurationsfile, mit `php.ini` für die gesamte Applikation konfigurieren.
Wir erstellen also ein `html/php.ini` file und fügen folgendes hinzu:

```text
[Session]  
session.cookie_httponly = 1
```

Nun wird direkt wenn der Benutzer die Website besucht der `PHPSESSID` Cokie auf `true` gesetzt.

![](https://uploads.mfellner.com/PDiWvDgcBwt3.png)

### 3.2 SameSite cookie setzen

Um jetzt zu verhindern, dass unser Session Cookie von dem Browser mitgesendet wird, müssen wir den `SameSite` cookie auf `strict` setzen.
Dies erfordert nur einen weiteren Eintrag im `php.ini` file:

```text
[Session]  
session.cookie_httponly = 1  
session.cookie_samesite = "Strict"
```

Wenn wir nun die App neustarten und die website besuchen, fällt uns das folgende auf:

![](https://uploads.mfellner.com/QBY1xT7CAaqU.png)

Der Cookie ist jetzt gesetzt!

## XSS

Aufgabenstellung:
>Behebe die XSS-Luecke im Code **nicht** direkt, sondern stelle mit einer entsprechenden Content Security Policy sicher, dass durch XSS-Luecken generell kein Schadcode ausgefuehrt werden kann [2]. Achte darauf, dass bestehende JavaScript-Inhalte weiter funktionieren.

Wir müssen hier also die CSP (Content Security Policy) verändern, um aufrufen von externen JavaScript oder CSS Dokumenten zu blockieren.

Was wir hierbei jedoch beachten müssen, ist, dass wir selbst für unsere Applikation folgende externe Inhalte laden:

```html
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">  
  
  
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>  
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.net.min.js"></script>
```

Das heißt, dass wir in unserer CSP zwar das externe laden von sonstigen Dokumenten verbieten, jedoch gewisse hosts (wie hier eben `"cdn.jsdelivr.net` und `cdnjs.cloudflare.com`) erlauben müssen.

Dadurch kommen wir auf folgenden CSP-String:

```
"Content-Security-Policy: default-src 'none'; script-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' cdn.jsdelivr.net 'unsafe-inline'; img-src 'self'; font-src 'self'; base-uri 'self'; form-action 'self';"
```
- **default-src**: Standardmäßig werden alle Ressourcenquellen blockiert, es sei denn, sie werden explizit in anderen Direktiven erlaubt.
- **script-src**: Erlaubt das Ausführen von internen JavaScript code + noch die zwei Content Delivery Hosts, welche wir für unsere Website verwenden (`cdnjs.cloudflare.com`& `cdn.jsdelivr.net`)
- **style-src**: Erlaubt das interpretieren von internen Style dokumenten + noch den einen host, über welchen wir ein Style Dokument laden (`cdn.jsdelivr.net`)
- 

