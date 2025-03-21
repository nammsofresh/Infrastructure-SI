# 📌 Projet : Service de Streaming Musical avec VPN & Extranet de Connexions SSH

Ce projet met en place un **service de streaming musical** basé sur **Navidrome**, sécurisé via un **VPN WireGuard**, et complété par un **extranet** affichant les connexions SSH actives sur un **VPS Ubuntu 24.04**.

## 🚀 Fonctionnalités

- **Navidrome** : Plateforme de streaming musicale auto-hébergée accessible via un reverse proxy Nginx.
- **WireGuard VPN** : Accès sécurisé au serveur et à Navidrome.
- **Extranet de supervision** : Interface web affichant en temps réel les connexions SSH actives.
- **Sécurisation du VPS** : Pare-feu, durcissement SSH et isolation des services via Docker.

## 🏗️ Architecture du Projet

Le projet repose sur un serveur Ubuntu 24.04 configuré avec plusieurs services interconnectés :

- **Navidrome** : Déployé via Docker et accessible via un reverse proxy Nginx.
- **WireGuard** : Fournit une connexion sécurisée pour accéder aux services internes.
- **Extranet PHP** : Interface permettant de surveiller les connexions SSH en cours.
- **Sécurité** : 
  - Firewall UFW pour restreindre les accès.
  - Authentification SSH via clés pour limiter les accès non autorisés.
  - Fail2Ban pour bloquer les tentatives d'intrusion.
  
mermaid
graph TD;
    A[Utilisateur] -->|Connexion VPN| B[WireGuard VPN 🔒];
    B --> C[Nginx Reverse Proxy 🌍];
    C -->|Flux musical| D[Navidrome 🎶];
    C -->|Affichage stats| E[Extranet 📊];

    subgraph Serveur VPS
        B
        C
        D
        E
    end


## 🔧 Déploiement et Configuration

1. **Installation de Navidrome** : Déploiement via Docker avec un stockage dédié pour les fichiers audio.
2. **Mise en place de WireGuard** : Création d'une configuration VPN pour sécuriser les accès.
3. **Configuration de Nginx** : Mise en place d'un reverse proxy pour sécuriser l'accès à Navidrome.
4. **Déploiement de l'extranet** : Installation d'un script PHP qui affiche les connexions SSH actives.
5. **Sécurisation du VPS** : Application des bonnes pratiques de sécurité.

## 🎯 Objectifs

- Offrir un service de streaming musical auto-hébergé et sécurisé.
- Garantir un accès distant sécurisé via un VPN.
- Fournir une visibilité sur les connexions SSH en temps réel.
- Appliquer des mesures de sécurité pour protéger le serveur.

## 📜 Conclusion

Ce projet permet d'expérimenter et de mettre en place plusieurs technologies essentielles pour l'auto-hébergement sécurisé de services en ligne. Il combine la gestion de conteneurs, la mise en place de VPN, l'administration système et la cybersécurité, offrant ainsi une solution complète et robuste.

Projet réalisé par Louis Chavaroche, Titouan Venant, Felix Conchy.