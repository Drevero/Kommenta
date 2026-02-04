# Kommenta

Plugin WordPress qui enrichit la section des commentaires en ajoutant un système de **votes par réactions** (positif, négatif, neutre) pour améliorer l’interaction avec votre communauté.

![WordPress](https://img.shields.io/badge/WordPress-5.2%2B-21759B?logo=wordpress)
![PHP](https://img.shields.io/badge/PHP-7.2%2B-777BB4?logo=php)
![License](https://img.shields.io/badge/License-GPL%20v2-green)

---

## ✨ Fonctionnalités

- **3 réactions** par commentaire : Positif, Négatif, Neutre
- **Barre visuelle** sous chaque commentaire : les segments sont proportionnels aux votes
- **Vote en un clic** : les visiteurs (connectés ou non) peuvent voter
- **Tooltip au survol** : affiche le libellé et le nombre de votes par réaction
- **Mise à jour en direct** : la barre se met à jour après chaque vote (sans rechargement)
- **Chargement optimisé** : CSS et JS chargés uniquement sur les articles (pages single)

---

## 📋 Prérequis

- **WordPress** : 5.2 ou supérieur  
- **PHP** : 7.2 ou supérieur  

---

## 🚀 Installation

1. Téléchargez le plugin ou clonez le dépôt :
   ```bash
   git clone https://github.com/VOTRE_USERNAME/kommenta.git
   ```
2. Copiez le dossier `kommenta` dans `wp-content/plugins/`.
3. Dans l’admin WordPress, allez dans **Extensions** et activez **Kommenta**.

Aucune configuration supplémentaire n’est nécessaire : les réactions apparaissent automatiquement sous les commentaires des articles.

---

## 📖 Utilisation

Sur un article (page single) :

1. Les commentaires affichent une **barre colorée** en dessous du texte :
   - **Vert** : Positif  
   - **Rouge** : Négatif  
   - **Bleu** : Neutre  

2. **Survol** d’un segment : un tooltip indique le libellé et le nombre de votes.

3. **Clic** sur un segment : enregistrement du vote et mise à jour immédiate des proportions de la barre.

---

## 📁 Structure du projet

```
kommenta/
├── assets/
│   ├── css/
│   │   └── komenta-front.css   # Styles de la barre et du tooltip
│   └── js/
│       └── komenta-front.js    # Gestion des clics, tooltip, appel AJAX
├── kommenta.php                # Point d’entrée du plugin
└── README.md
```

# Always WORK IN PROGRESS, do not use today in prod

*Si ce plugin vous est utile, n’hésitez pas à mettre une ⭐ sur le dépôt.*
