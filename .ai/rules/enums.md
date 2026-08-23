---
paths:
  - 'app/Enums/**'
---

# Enums

## GenerationProvider owns AI model metadata
Keep capability support, fixed text/image model options, defaults, and custom-model behavior in GenerationProvider. AiModelCatalog only resolves and validates selections; config/wallpaper.php stores provider defaults, not model catalogs.
