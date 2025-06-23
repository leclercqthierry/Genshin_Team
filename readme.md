# Genshin_Team

**Genshin_Team** est une application web PHP permettant de gérer des équipes et des utilisateurs autour de l’univers du jeu Genshin Impact.  
Elle propose un système d’inscription, d’authentification, de gestion de sessions, et une architecture MVC moderne avec tests unitaires.

---

## Fonctionnalités principales

- Inscription et connexion sécurisées des utilisateurs (avec CSRF, validation, hashage des mots de passe)
- Gestion des équipes et des membres
- Affichage dynamique des vues avec un moteur de rendu simple
- Architecture MVC (Modèle-Vue-Contrôleur)
- Couche d’accès aux données via PDO
- Gestion centralisée des erreurs et des logs
- Tests unitaires complets avec PHPUnit
- Les fichiers des dossiers src et Tests passent le niveau 10 de phpstan

---

## Structure du projet

```
.
├── src/
│   ├── Connexion/         # Gestion de la connexion à la base de données
│   ├── Controllers/       # Contrôleurs (Register, Login, Logout, etc.)
│   ├── Models/            # Modèles (User, Team, etc.)
│   ├── Renderer/          # Moteur de rendu de vues
│   ├── Session/           # Gestion des sessions
│   └── Utils/             # Outils divers (validation, erreurs, etc.)
├── Tests/
│   ├── Unitaries/         # Tests unitaires PHPUnit
│   └── ...
├── views/                 # Vues PHP (templates)
├── public/                # Fichiers accessibles publiquement (index.php, assets, etc.)
├── .env                   # Variables d’environnement (MySQL, etc.)
├── composer.json
└── README.md
```

---

## Installation

### Prérequis

- PHP 8.1+
- Composer
- (Optionnel) Docker

### Installation classique

1. Clonez le dépôt :

    ```bash
    git clone <url-du-repo>
    cd Genshin_Team
    ```

2. Installez les dépendances :

    ```bash
    composer install
    ```

3. Configurez votre base de données dans le fichier `.env` (voir `.env.example`).

4. Lancez le serveur PHP :
    ```bash
    php -S localhost:80 -t public
    ```

### Avec Docker

1. Construisez et lancez les conteneurs :

    ```bash
    docker-compose up --build
    ```

2. L’application sera accessible sur [http://localhost:80], PHPMyAdmin sur [http://localhost:8080]

---

## Lancer les tests

```bash
# En local
vendor/bin/phpunit --colors --testdox

# Avec Docker
docker exec -it php-container vendor/bin/phpunit --colors --testdox --coverage-html coverage-report
```

Le rapport de couverture sera généré dans le dossier `coverage-report`.

---

## Contribution

Les contributions sont les bienvenues !  
Merci de :

- Forker le projet
- Créer une branche pour vos modifications
- Proposer une Pull Request

---

## Licence

Projet sous licence MIT.

---

## Auteurs

- Leclercq Thierry

---

## Remerciements

- [Genshin Impact](https://genshin.hoyoverse.com/) pour l’inspiration
- [PHPUnit](https://phpunit.de/) pour les tests unitaires
