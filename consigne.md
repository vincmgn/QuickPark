### Consignes du Projet QuickPark

Le choix de la problématique ainsi que le nombre d'apprenants par projet reste au choix des apprenants. Cependant, par souci d'équité, une discussion préalable a été menée afin de s'accorder sur un rendu quantitativement fixé à l'amiable entre les apprenants de chaque groupe et le formateur, permettant ainsi de vérifier la capacité des apprenants à quantifier leurs travaux et ainsi l'améliorer (non noté).

De manière générale, l'application ne peut pas implémenter que des fonctionnalités endémiques à un CRUD. Elle doit implémenter au moins 3 mécaniques customisées telles que :
- Scraping automatisé avec un CRON

### Répartition des Points

15 points sont distribués pour l'ensemble des critères suivants :

1. **Langage et Normes** (2,5 points)
    - L'application Back doit être développée dans un langage libre parmi ceux que je maîtrise (PHP, GoLang, Python, C, C++, C#) et répondre aux normes de code quality (PSR vu en cours).
    - Répondre aux 5 contraintes obligatoires d'une API REST (vu en cours l'année dernière et approfondies cette année avec des prérogatives de code quality).

2. **Tests** (3 points)
    - L'ensemble de l'application doit être accompagnée par des tests unitaires et des tests de non-régression.

3. **Cache** (1 point)
    - L'application Back doit implémenter un système de cache.

4. **Middleware** (1 point)
    - L'application Back doit avoir au moins un Middleware (utile pour la gestion d'erreur par exemple).

5. **Routes et Utilisateurs** (3 points)
    - L'application Back doit distribuer des routes d'une API avec un système d'utilisateur, de rôles, et d'enregistrement de fichier au minimum.

6. **Échelle de Richardson** (2,5 points)
    - L'application Back doit s'élever au niveau 4 de l'échelle de Richardson.

7. **Fonctionnalités Avancées** (1 point)
    - L'application Back doit être prête à implémenter l'historisation, l'anonymisation des utilisateurs et une gestion (légère) des statistiques.

8. **Documentation** (1 point)
    - L'application Back doit être documentée au mieux (installation du projet, fonctionnalités de l'application).

### Qualité et Bonus

Le reste de la note est attribué sur :
- La qualité de code
- La couverture des tests
- La pertinence des commits & commentaires
- L'implication dans le projet (si l'apprenant est en groupe)

#### Bonus

- **Technologie Ingénieuse** (0 à 2 points, non cumulable)
  - Utilisation ingénieuse d'une technologie pertinente par rapport à la problématique (ex : un ERP utilisant Electron).

- **Comportement Exemplaire** (1 point, non cumulable)
  - Entraide d'un élève plus expérimenté à un élève en difficulté ou une remarque très pertinente en cours.

### Contraintes Éliminatoires

- L'application n'est pas accessible publiquement (l'utilisation de GitHub est fortement conseillée).
- L'application n'a pas de jeu de données de test prêt à l'emploi (Fixture ou autre).
- L'application n'a pas de procédure d'installation.
- La procédure d'installation de l'application ne fonctionne pas sur l'ensemble de mes systèmes d'exploitation : Linux, Windows, MacOS.

### Remarques

- Aucun bonus n'est attribué quant au choix de la problématique, aussi compliquée soit-elle, ou à l'esthétique de l'application, ou au développement d'un front aussi complexe ou pertinent soit-il.
- Aucun bonus ou malus n'est attribué quant à la discipline de l'apprenant (si ce ne sont les points sus-mentionnés), ou son comportement vis-à-vis du formateur.