import { defineConfig } from 'vitepress';

// https://vitepress.dev/reference/site-config
export default defineConfig({

  title: "Sidekick",
  description: "Your AI companion for rapid Craft CMS development.",

  base: '/sidekick/',
  cleanUrls: true,

  themeConfig: {

    logo: '/images/icon.svg',
    search: {provider: 'local'},

    // https://vitepress.dev/reference/default-theme-config
    nav: [
      {text: 'Getting Started', link: '/getting-started/'},
      {
        text: 'Chat Window',
        items: [
          {
            items: [
              {text: 'How It Works', link: '/chat/how-it-works'},
              {text: 'Native Skills', link: '/chat/native-skills'},
              {text: 'Custom Skills', link: '/chat/custom-skills'},
              {text: 'Custom Prompts', link: '/chat/custom-prompts'},
            ]
          }
        ]
      },
      {
        text: 'Customize',
        items: [
          {
            items: [
              {text: 'Add Prompts', link: '/customize/add-prompts'},
              {text: 'Add Skills', link: '/customize/add-skills'},
            ]
          }
        ]
      },
      {
        text: 'More',
        items: [
          {
            items: [
              {text: '"AI Summary" Field Type', link: '/fields/ai-summary'},
              {text: 'Troubleshooting', link: '/troubleshooting/'},
              {text: 'Disclaimers', link: '/disclaimers/'},
            ]
          }
        ]
      },
    ],

    sidebar: {
      // Getting Started
      '/getting-started/': [
        {
          text: 'Getting Started',
          items: [
            {
              text: 'Overview',
              link: '/getting-started/',
              items: [
                {text: 'Install via Plugin Store', link: '/getting-started/#installation-via-plugin-store'},
                {text: 'Install via CLI', link: '/getting-started/#installation-via-console-commands'}
              ]
            },
            {text: 'Settings Page', link: '/getting-started/settings'},
            {text: 'PHP Config File', link: '/getting-started/config'}
          ]
        }
      ],

      // Chat Window
      '/chat/': [
        {
          text: 'Chat Window',
          items: [
            {text: 'How It Works', link: '/chat/how-it-works'},
            {text: 'Native Skills', link: '/chat/native-skills'},
            {text: 'Custom Skills', link: '/chat/custom-skills'},
            {text: 'Custom Prompts', link: '/chat/custom-prompts'},
          ]
        }
      ],

      // Customize
      '/customize/': [
        {
          text: 'Customize',
          items: [
            {
              text: 'Add Prompts',
              link: '/customize/add-prompts',
              items: [
                {text: 'eg: Attitude Adjustment', link: '/customize/examples/attitude-adjustment'},
                {text: 'eg: Describe a Process', link: '/customize/examples/describe-a-process'},
                {text: 'eg: Explain Architecture', link: '/customize/examples/explain-architecture'},
              ]
            },
            {
              text: 'Add Skills',
              link: '/customize/add-skills',
              items: [
                {text: 'eg: Send An Email', link: '/customize/examples/send-an-email'},
                {text: 'eg: Create a Report', link: '/customize/examples/create-report'},
                {text: 'eg: Add To Calendar', link: '/customize/examples/add-to-calendar'}
              ]
            },
          ]
        }
      ],

      // Fields
      '/fields/': [
        {
          text: 'Fields',
          items: [
            {text: '"AI Summary" Field Type', link: '/fields/ai-summary'},
          ]
        }
      ],

      // Troubleshooting
      '/troubleshooting/': [
        {
          text: 'Troubleshooting',
          items: [
            {text: 'Tips', link: '/troubleshooting/'},
          ]
        }
      ],

      // Disclaimers
      '/disclaimers/': [
        {
          text: 'Disclaimers',
          items: [
            {text: 'You are still in charge.', link: '/disclaimers/leadership'},
            {text: 'Watch your changes!', link: '/disclaimers/quality-assurance'},
            {text: 'We are not liable for mishaps.', link: '/disclaimers/liability'},
          ]
        }
      ],

    },

    aside: false, // Hide right-hand sidebar for page anchors

    socialLinks: [
      {
        icon: {
          svg: `
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" aria-hidden="true">
    <title>Plugin Store</title>
    <!--! Font Awesome Pro 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. -->
    <path d="M96 0C78.3 0 64 14.3 64 32v96h64V32c0-17.7-14.3-32-32-32zM288 0c-17.7 0-32 14.3-32 32v96h64V32c0-17.7-14.3-32-32-32zM32 160c-17.7 0-32 14.3-32 32s14.3 32 32 32v32c0 77.4 55 142 128 156.8V480c0 17.7 14.3 32 32 32s32-14.3 32-32V412.8C297 398 352 333.4 352 256V224c17.7 0 32-14.3 32-32s-14.3-32-32-32H32z"/>
</svg>`
        },
        link: 'https://plugins.craftcms.com/sidekick',
        ariaLabel: 'Plugin Store'
      },
      {
        icon: 'github',
        link: 'https://github.com/doublesecretagency/craft-sidekick'
      }
    ],

    docFooter: {
      prev: false,
      next: false
    },

    footer: {
      copyright: 'Copyright © Double Secret Agency'
    },

  }
})
