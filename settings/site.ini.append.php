<?php /*

[TemplateSettings]
ExtensionAutoloadPath[]=mugo_memcache

[ContentSettings]
StaticCache=enabled
StaticCacheHandler=MugoMemCacheHandler

# We keep ViewCaching enabled - so that the Static Cache handler
# gets triggered. But we removed all CacheViewModes to stop ezp
# from generating view cache files.
ViewCaching=enabled
#CachedViewModes=

*/ ?>