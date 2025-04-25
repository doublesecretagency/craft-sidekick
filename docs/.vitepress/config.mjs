import { defineConfig } from 'vitepress';

const metaUrl = 'https://plugins.doublesecretagency.com/sidekick/';
const metaTitle = 'Sidekick plugin for Craft CMS';
const metaDescription = 'Your AI companion for rapid Craft CMS development.';
const metaImage = 'https://plugins.doublesecretagency.com/sidekick/images/meta/sidekick.png';

// https://vitepress.dev/reference/site-config
export default defineConfig({

  title: "Sidekick plugin",
  description: "Your AI companion for rapid Craft CMS development.",

  head: [
    ['meta', {'name': 'og:type', 'content': 'website'}],
    ['meta', {'name': 'og:url', 'content': metaUrl}],
    ['meta', {'name': 'og:title', 'content': metaTitle}],
    ['meta', {'name': 'og:description', 'content': metaDescription}],
    ['meta', {'name': 'og:image', 'content': metaImage}],
    ['meta', {'name': 'twitter:card', 'content': 'summary_large_image'}],
    ['meta', {'name': 'twitter:url', 'content': metaUrl}],
    ['meta', {'name': 'twitter:title', 'content': metaTitle}],
    ['meta', {'name': 'twitter:description', 'content': metaDescription}],
    ['meta', {'name': 'twitter:image', 'content': metaImage}],
  ],

  base: '/sidekick/',
  cleanUrls: true,

  themeConfig: {

    logo: '/images/icon.svg',
    search: {provider: 'local'},

    // https://vitepress.dev/reference/default-theme-config
    nav: [
      {text: 'Getting Started', link: '/getting-started/'},
      {text: 'Chat Window', link: '/chat-window/'},
      {
        text: 'Skills',
        items: [
          {
            items: [
              {text: 'Native Skills', link: '/native-skills/'},
              {text: 'Custom Skills', link: '/custom-skills/'},
            ]
          }
        ]
      },
      {text: 'AI Summary Field', link: '/fields/ai-summary'},
      {text: 'Disclaimers', link: '/disclaimers/'},
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
      '/chat-window/': [
        {
          text: 'Chat Window',
          items: [
            {
              text: 'Overview',
              link: '/chat-window/',
              items: [
                {text: 'How it Works', link: '/chat-window/#how-it-works'},
                {text: 'Message Types', link: '/chat-window/#message-types'},
                {text: 'Clearing the Conversation', link: '/chat-window/#clearing-the-conversation'},
                {text: 'Switching GPT Models', link: '/chat-window/#switching-gpt-models'},
              ]
            }
          ]
        }
      ],

      // Native Skills
      '/native-skills/': [
        {
          text: 'Native Skills',
          items: [
            {
              text: 'Overview',
              link: '/native-skills/',
              items: [
                {text: 'Templates', link: '/native-skills/#templates'},
                {text: 'Entries', link: '/native-skills/#entries'},
                {text: 'Settings - Sections', link: '/native-skills/#settings-sections'},
              ]
            }
          ]
        }
      ],

      // Custom Skills
      '/custom-skills/': [
        {
          text: 'Custom Skills',
          items: [
            {text: 'Overview', link: '/custom-skills/'},
            {
              text: 'Examples',
              items: [
                {text: 'Send An Email', link: '/custom-skills/examples/send-an-email'},
                {text: 'Add To Calendar', link: '/custom-skills/examples/add-to-calendar'}
              ]
            }
          ]
        }
      ],

      // Fields
      '/fields/': [
        {
          text: 'Fields',
          items: [
            {text: 'AI Summary Field Type', link: '/fields/ai-summary'},
            {text: 'How does it work?', link: '/fields/ai-summary#how-does-it-work'},
          ]
        }
      ],

      // Disclaimers
      '/disclaimers/': [
        {
          text: 'Disclaimers',
          items: [
            {text: 'Overview', link: '/disclaimers/'},
            {text: 'You are still in charge.', link: '/disclaimers/leadership'},
            {text: 'Watch your changes!', link: '/disclaimers/quality-assurance'},
            {text: 'We are not responsible for any mishaps.', link: '/disclaimers/liability'},
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
