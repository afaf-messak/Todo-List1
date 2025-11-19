# 📋 ToDo List - Projet en Binôme

## 📝 Présentation

Ce projet est une application de gestion de tâches (**ToDo List**) réalisée en **PHP** et **MySQL**, avec une interface utilisateur moderne en **HTML** et **CSS**.

L'objectif est de vous permettre de gérer efficacement vos tâches quotidiennes : ajouter, terminer, visualiser par date, ou supprimer une tâche.

---

## ✨ Fonctionnalités

- ➕ **Ajouter une tâche** : Saisissez le titre et ajoutez une nouvelle tâche à votre liste.
- ✅ **Marquer comme terminée** : Cochez une tâche pour l’indiquer comme faite ou non faite.
- 🗑️ **Suppression** : Supprimez les tâches dont vous ne voulez plus.
- 📅 **Organisation par date** : Les tâches sont affichées regroupées par date de création.
- 🎨 **Interface moderne** : Design responsive et épuré avec une expérience utilisateur fluide.

---

## 🚀 Technologies utilisées

- 💻 **Backend** : PHP (PDO & MySQL)
- 🗄️ **Base de données** : MySQL
- 🌐 **Frontend** : HTML5, CSS3 (Flexbox, Google Fonts)

---

## ⚙️ Installation

1. **Cloner le dépôt :**
   ```bash
   git clone https://github.com/afaf-messak/Todo-List1.git
   ```

2. **Configuration de la base de données :**

   - Créez une base de données nommée `todo-list`.
   - Exécutez la requête suivante pour créer la table :
     ```sql
     CREATE TABLE todo (
         id INT AUTO_INCREMENT PRIMARY KEY,
         title VARCHAR(255) NOT NULL,
         done TINYINT(1) DEFAULT 0,
         created_at DATETIME DEFAULT CURRENT_TIMESTAMP
     );
     ```
   - Modifiez les informations de connexion dans `index1.php` si nécessaire (utilisateur, mot de passe, hôte).

3. **Lancer l’application :**
   - Ouvrez `index1.php` dans votre navigateur via un serveur local (ex : XAMPP, WAMP, MAMP).

---

## 👩‍💻👩‍💻 Auteurs

Ce travail a été réalisé en binôme par :

- 👩 **Khadija Fatihi**
- 👩 **Afaf Messak**

---

Merci d’avoir utilisé notre application ! 💖  
Pour toute suggestion ou amélioration, n’hésitez pas à créer une issue ou un pull request
