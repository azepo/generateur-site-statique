# language: fr
@javascript
Fonctionnalité: Accessibilité du site généré, dans un navigateur
  Chaque contrôle a son propre plan de scénario. Ajouter chaque nouvelle page aux exemples.

  Plan du scénario: Les règles de rgaa-check s'appliquent à la page affichée
    Étant donné que je suis sur "<page>"
    Alors la page ne doit présenter aucune erreur d'accessibilité

    Exemples:
      | page                  |
      | /index.html           |
      | /plan_du_site.html    |
      | /accessibilite.html   |
      | /exemple-article.html |

  Plan du scénario: La page ne défile pas horizontalement sur un petit écran
    Étant donné que je suis sur "<page>"
    Alors la page ne doit pas défiler horizontalement à 320 pixels de large

    Exemples:
      | page                  |
      | /index.html           |
      | /plan_du_site.html    |
      | /accessibilite.html   |
      | /exemple-article.html |

  Plan du scénario: Les contrastes du texte sont suffisants
    Étant donné que je suis sur "<page>"
    Alors les contrastes du texte doivent être suffisants

    Exemples:
      | page                  |
      | /index.html           |
      | /plan_du_site.html    |
      | /accessibilite.html   |
      | /exemple-article.html |

  Plan du scénario: Le lien d'évitement mène au contenu
    Étant donné que je suis sur "<page>"
    Alors le lien d'évitement doit mener au contenu principal

    Exemples:
      | page                  |
      | /index.html           |
      | /plan_du_site.html    |
      | /accessibilite.html   |
      | /exemple-article.html |

  Plan du scénario: La page se parcourt au clavier
    Étant donné que je suis sur "<page>"
    Alors on doit pouvoir parcourir la page au clavier

    Exemples:
      | page                  |
      | /index.html           |
      | /plan_du_site.html    |
      | /accessibilite.html   |
      | /exemple-article.html |

  Plan du scénario: Le focus est visible
    Étant donné que je suis sur "<page>"
    Alors le focus doit être visible sur chaque élément atteint au clavier

    Exemples:
      | page                  |
      | /index.html           |
      | /plan_du_site.html    |
      | /accessibilite.html   |
      | /exemple-article.html |
