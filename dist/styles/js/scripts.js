/* ═══════════════════════════════════════════════════════
   DATA + CONFIG
═══════════════════════════════════════════════════════ */
const QUICK_CMDS = {
  bash:  ['ls -la','pwd','php -v','cat wp-config.php | grep DB_NAME','df -h .'],
  wpcli: ['wp status','wp plugin list','wp theme list --status=active',
          'wp user list','wp post list --post_type=post --posts_per_page=5',
          'wp option get siteurl','wp db size','wp cron event list',
          'wp cache flush','wp transient delete --all'],
};

/* ═══════════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════════ */
const S = {
  theme:      localStorage.getItem('sh-theme') || 'dark',
  view:       localStorage.getItem('sh-view')  || 'grid',
  filter:     'all',
  sort:       'az',
  typeFilter: 'all',
  project:  localStorage.getItem('sh-proj')  || '',
  tasks:    {},
  dbg:      {},           // { [site]: bool|'?' }
  tp: {
    open:   false,
    site:   null,
    mode:   'bash',
    hist:   [],
    hIdx:   -1,
  },
  logSite:  null,
  dragging: null,
  php:      {},   // { [site]: 'X.Y' }
};

/* ═══════════════════════════════════════════════════════
   UTILITIES
═══════════════════════════════════════════════════════ */
const $  = (sel, ctx=document) => ctx.querySelector(sel);
const $$ = (sel, ctx=document) => [...ctx.querySelectorAll(sel)];
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
