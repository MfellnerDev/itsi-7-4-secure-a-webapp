***
**Autor:** Manuel Fellner
**Version:** 12.01.2024

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


### 2.1 Passwörter hashen

Als erstes beschäftigen wir uns damit, dass die Passwörter in der Datenbank gehasht gespeichert werden.
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
Die `usleep()` Methode (https://www.php.net/manual/en/function.usleep) gibt uns die Möglichkeit, den Ablauf der App um beliebig viel Mikrosekunden zu verzögern.

Also bauen wir bei den zwei oben genannten Use-Cases eine künstliche Verzögerung ein:

```php
// if the username is incorrect  
if ($result->num_rows === 0) {  
    usleep(rand(70000, 200000)); // Delay between 70ms and 200ms  
    header("Location: login.html");  
    exit();  
}  
  
$row = $result->fetch_assoc();  
$storedPassword = $row['password'];  
  
// if the username is correct -> secure password comparison  
if (!hash_equals($storedPassword, crypt($password, $storedPassword))) {  
    usleep(rand(20000, 50000)); // Delay between 20ms and 50ms  
    header("Location: login.html");  
    exit();  
}
```

Nun sind die Antwortzeiten in beiden Fällen zwar nicht absolut gleich, jedoch sind die inkonsistent und zufällig. Da der Bereich der Zufalls-milisekunden im Use-Case 1 größer ist, gehen wir damit sicher, dass die Antwortzeit bei Use-Case 2 in den meisten Fällen kleiner als die des Use-Cases 1 ist.
Damit kann also die Antwortzeit bei einem falschen Benutzernamen sogar um 10-20ms länger sein, als bei einem richtigen Benutzernamen.


Falscher Benutzername:
![](https://uploads.mfellner.com/XpYvEFFqnZzw.png)

Antwortzeit: 176ms

Richtiger Benutzername:
![](https://uploads.mfellner.com/OfuORd9t9Jr9.png)

Antwortzeit: 134ms.

Man kann im obigen Code auch sehen, dass wir jetzt `hash_equals` (https://www.php.net/manual/en/function.hash-equals) verwenden.
> hash_equals — Timing attack safe string comparison

> Checks whether two strings are equal without leaking information about the contents of `known_string` via the execution time.

>This function can be used to mitigate timing attacks. Performing a regular comparison with `===` will take more or less time to execute depending on whether the two values are different or not and at which position the first difference can be found, thus leaking information about the contents of the secret `known_string`.
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

## 4. XSS

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
Content-Security-Policy: default-src 'none'; script-src 'self' cdn.jsdelivr.net cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' cdn.jsdelivr.net 'unsafe-inline'; img-src 'self'; frame-src 'none'; form-action 'self'
```
- **default-src**: Standardmäßig werden alle Ressourcenquellen blockiert, es sei denn, sie werden explizit in anderen Direktiven erlaubt.
- **script-src**: Erlaubt das Ausführen von internen JavaScript code + noch die zwei Content Delivery Hosts, welche wir für unsere Website verwenden (`cdnjs.cloudflare.com`& `cdn.jsdelivr.net`)
- **style-src**: Erlaubt das interpretieren von internen Style dokumenten + noch den einen host, über welchen wir ein Style Dokument laden (`cdn.jsdelivr.net`)
- **img-src**: Erlaubt das laden von Bilden nur von der eigenen Domain
- **frame-src**: Verhindert das einbetten der Website in frames
- **form-action**: HTML Forms können nur an den eigenen origin gesendet werden

Wenn wir jetzt also in die Developer Konsole gehen und z.B. versuchen, ein externes Script zu laden (in unserem Fall verwenden wir einfach mal JQuery
`https://code.jquery.com/jquery-3.3.1.slim.min.js`):

![](https://uploads.mfellner.com/DLJxu7gYKEf2.png)

- laden von lokalen Javascript mittels nonce regulieren

## 5. WAF

Aufgabenstellung:
>Aendere das Deployment der Anwendung so ab, dass der PHP-Code nicht direkt von aussen aufgerufen wird, sondern hinter einer Web Application Firewall (WAF), wie zum Beispiel mod_security [3]. Dies kann entweder als Apache-Modul oder als Reverse Proxy ausgefuehrt sein.

Wir sollen jetzt also ein WAF (Web Application Firewall) konfigurieren, um uns vor möglichen Injection-Angriffen zu schützen.

![](https://uploads.mfellner.com/FUklBpwtaOkC.png)

(siehe https://www.cloudflare.com/learning/ddos/glossary/web-application-firewall-waf/)

### 5.1 mod secure als PHP modul hinzufügen

Hier werde ich der folgenden Anleitung folgen:
https://medium.com/@luckbareja/configuring-modsecurity-web-application-firewall-37eb409e1235

1. Wir begeben uns in den Docker container
```shell
$ docker exec -it vulnerable_app-website-1
```

2. Danach gehen wir in das`/etc/apache2/` directory und erstellen ein directory namens `modsecurity.d`
3. Nun gehen wir in das `/etc/apache2/modsecurity.d` directory und clonen https://github.com/coreruleset/coreruleset
4. Es wurde jetzt ein Ordner namens `coreruleset` erstellt, in dem wir uns jetzt begeben
5. Now go further into rules folder and rename REQUEST-900-EXCLUSION-RULES-BEFORE-CRS.conf.example to REQUEST-900-EXCLUSION-RULES-BEFORE-CRS.conf

6. Rename RESPONSE-999-EXCLUSION-RULES-AFTER-CRS.conf.example to RESPONSE-999-EXCLUSION-RULES-AFTER-CRS.conf
7. Wir gehne zu `/etc/apache2/apache2.conf` und fügen den folgenden Eintrag hinzu:
```text
<IfModule security2_module>  
Include modsecurity.d/coreruleset/crs-setup.conf  
Include modsecurity.d/coreruleset/rules/*.conf
</IfModule>
```

8. Container restarten

Damit sollte die WAF jetzt eigentlich funktionieren
