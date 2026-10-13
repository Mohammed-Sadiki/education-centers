# 🎓 Education Centers — Plateforme de Gestion Multi-Centres

Application web moderne développée avec **Laravel 11** et **Tailwind CSS** pour la gestion administrative, pédagogique et financière de centres d'éducation et de formation multi-villes (Berlin, Munich, etc.).

---

## 📋 Prérequis

Avant de commencer, assurez-vous d'avoir installé sur votre machine :

- **PHP 8.3** ou version supérieure
  - Extensions PHP requises : `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`, `fileinfo`
- **Composer** (v2.x)
- **Node.js** (v18.x ou supérieur, recommandé v20+) & **NPM**
- **MySQL** (v8.0+) ou **MariaDB** (ou SQLite)
- **Git**

> 💡 **Utilisateurs Windows :** Si PHP est installé dans un répertoire spécifique (ex: `C:\php83`), assurez-vous d'ajouter ce chemin à votre variable d'environnement système `PATH`, ou utilisez le script `serve.bat` fourni à la racine.

---

## 🚀 Guide d'Installation Rapide

Suivez ces étapes pour installer et lancer le projet après avoir cloné le dépôt :

### 1. Cloner le projet et accéder au dossier

```bash
git clone https://github.com/Mohammed-Sadiki/education-centers.git
cd education-centers
```

*(Si vous avez cloné le dépôt complet contenant le sous-dossier, placez-vous dans `education-centers`)*

### 2. Installer les dépendances PHP (Composer)

```bash
composer install
```

### 3. Configurer l'environnement (.env)

Copiez le fichier d'exemple pour créer votre fichier `.env` :

- **Sous Windows (PowerShell / CMD) :**
  ```powershell
  copy .env.example .env
  ```
- **Sous Linux / macOS :**
  ```bash
  cp .env.example .env
  ```

Générez la clé de chiffrement applicative :

```bash
php artisan key:generate
```

### 4. Configurer la Base de Données

Créez une base de données vide nommée `education_centers` dans votre serveur MySQL / MariaDB (ex: via phpMyAdmin ou MySQL Workbench) :

```sql
CREATE DATABASE education_centers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Vérifiez ensuite les paramètres de connexion dans votre fichier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=education_centers
DB_USERNAME=root
DB_PASSWORD=
```

*(Optionnel) Si vous préférez utiliser SQLite sans serveur de base de données :*
```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```
*(Créez un fichier vide `database/database.sqlite` le cas échéant).*

### 5. Exécuter les migrations et les seeders

Exécutez les migrations pour créer les tables et chargez les données de démonstration :

```bash
php artisan migrate --seed
```

### 6. Créer le lien symbolique pour le stockage de fichiers

Nécessaire pour le téléchargement et l'affichage des documents, avatars et pièces jointes :

```bash
php artisan storage:link
```

### 7. Installer et compiler les dépendances Front-end (NPM)

```bash
npm install
npm run build
```

Pour travailler en direct sur le CSS et le JavaScript avec rechargement à chaud (Hot Module Replacement) :
```bash
npm run dev
```

### 8. Lancer le serveur local

```bash
php artisan serve
```

> **Sous Windows :** Vous pouvez également double-cliquer sur `serve.bat` ou exécuter `.\serve.bat`.

L'application est maintenant accessible sur : **http://localhost:8000** (ou http://127.0.0.1:8000).

---

## 👥 Comptes de Démonstration (Seeders)

Tous les comptes créés par les seeders utilisent le mot de passe suivant :
👉 **`EducationAdmin2026!`**

| Rôle | Email | Centre | Accès |
|---|---|---|---|
| **Super Admin** | `admin@education-centers.de` | Global (tous) | Accès complet, gestion multi-centres |
| **Admin Centre** | `admin.berlin@education-centers.de` | Berlin | Gestion administrative du centre Berlin |
| **Admin Centre** | `admin.munich@education-centers.de` | Munich | Gestion administrative du centre Munich |
| **Professeur** | `prof.berlin@education-centers.de` | Berlin | Emploi du temps, présences, notes |
| **Professeur** | `prof.munich@education-centers.de` | Munich | Emploi du temps, présences, notes |
| **Élève** | `eleve.berlin@education-centers.de` | Berlin | Espace apprenant, formations, documents |
| **Élève** | `eleve.munich@education-centers.de` | Munich | Espace apprenant, formations, documents |

---

## 🧪 Tests Automatisés

Le projet intègre une suite complète de **217 tests unitaires et fonctionnels** exécutés sur une base SQLite en mémoire (aucun risque pour votre base de données locale).

Pour exécuter tous les tests :

```bash
php artisan test
```

---

## 🛠️ Dépannage & Problèmes Fréquents

### 1. `php : Le terme «php» n'est pas reconnu...`
Ce message indique que PHP n'est pas déclaré dans les variables d'environnement de votre système Windows.
- **Solution rapide :** Dans PowerShell, exécutez temporairement :
  ```powershell
  $env:Path = "C:\php83;" + $env:Path
  ```
  *(remplacez `C:\php83` par le dossier réel où vous avez extrait PHP)*.
- **Solution permanente :** Ouvrez le menu Démarrer > *Modifier les variables d'environnement système* > *Variables d'environnement* > Modifier `Path` (dans Variables utilisateur ou système) > Ajouter le chemin vers votre dossier PHP (ex: `C:\php83`).

### 2. `composer : Le terme «composer» n'est pas reconnu...`
Si Composer n'est pas installé globalement :
- Téléchargez `composer-setup.exe` depuis [getcomposer.org](https://getcomposer.org/)
- Ou exécutez directement via PHP : `php composer.phar install`

### 3. Erreur de permissions sur `storage/` et `bootstrap/cache/` (Linux / macOS)
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 📄 Licence

Projet développé sous licence MIT.
