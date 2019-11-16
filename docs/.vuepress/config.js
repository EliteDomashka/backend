module.exports = {
  title: 'EliteDomashka',
  description: 'Провайдер твоєї домашки',
  base: '/',
  head: [
    ['link', { rel: 'stylesheet', href: 'https://use.fontawesome.com/releases/v5.2.0/css/all.css', integrity: 'sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ', crossorigin: 'anonymous' }],
    ['link', { rel: 'shortcut icon', href: '/favicon.ico'}]
  ],
  serviceWorker: true,
  markdown: {
    lineNumbers: true,
    config: md => {
      md.use(require('markdown-it-footnote')),
      md.use(require('markdown-it-sub')),
      md.use(require('markdown-it-sup')),
      md.use(require('markdown-it-ins')),
      md.use(require('markdown-it-mark')),
      md.use(require('markdown-it-deflist')),
      md.use(require('markdown-it-abbr'))
    }
  },
  themeConfig: {
    logo: '/assets/img/logo.png',
    nav: [
      { icon: 'fas fa-book', iconClass: 'has-text-info', text: 'Посібник', link: '/user/' },
      { icon: 'fab fa-lg fa-github', iconClass: 'is-medium', text: 'GitHub', link: 'https://gitlab.com/EliteDomashka/new-backend' },
      { icon: 'fab fa-lg fa-telegram', iconClass: 'is-medium has-text-info', text: 'Telegram', link: 'https://t.me/EliteDomashka' }

      // { text: 'Dropdown', items: [ //disbled in NavLink.vue
      //   { text: 'Google', link: 'https://www.google.com' },
      //   { text: 'And google!', link: 'https://www.google.co.th/' }
      // ] }
    ],
    displayAllHeaders: false,
    sidebarDepth: 3,
    serviceWorker: true,
    search: false,
    sidebar: [
      {
        title: 'Посібник',
        icon: 'fas fa-book',
        collapsable: false,
        children: [
          '/user/',
          '/user/start'
        ]
      },
      {
        title: 'Документація',
        icon: 'fas fa-code',
        collapsable: false,
        children: [
          '/dev/',
          '/dev/install'
        ]
      },
      // {
      //   title: 'DEMO!',
      //   icon: 'fas fa-star',
      //   // iconClass: 'has-background-success has-text-warning button is-rounded',
      //   collapsable: false,
      //   children: [
      //     '/lorem/',
      //     '/lorem/article',
      //     '/lorem/frontmatter',
      //     '/lorem/table',
      //     '/lorem/emoji',
      //     '/lorem/custom-containers',
      //     '/lorem/code-blocks',
      //     '/lorem/markdown-it',
      //     '/lorem/markdown-vuepress',
      //     '/lorem/custom-layout'
      //   ]
      // }
    ],
    displayAllHeaders: true,
    repo: 'nakorndev/vuepress-theme-bulma',
    repoLabel: 'Contribute!',
    docsRepo: 'nakorndev/vuepress-theme-bulma',
    docsDir: 'demo',
    docsBranch: 'master',
    editLinks: true,
    editLinkText: 'Edit me!',
    lastUpdated: 'Остання правка:',
  }
};
