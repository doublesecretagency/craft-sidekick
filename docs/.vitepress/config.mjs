import { defineConfig } from 'vitepress';

const metaUrl = 'https://plugins.doublesecretagency.com/sidekick/';
const metaTitle = 'Sidekick plugin for Craft CMS';
const metaDescription = 'Build complex Craft sites in the blink of an eye.';
const metaImage = 'https://plugins.doublesecretagency.com/sidekick/images/meta/sidekick.png';

// https://vitepress.dev/reference/site-config
export default defineConfig({

  title: "Sidekick plugin",
  description: "Build complex Craft sites in the blink of an eye.",

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
      {
        text: 'Features',
        items: [
          {
            items: [
              {text: 'Chat Window', link: '/features/chat-window'},
              {text: 'Define Extra Tools Event', link: '/features/define-extra-tools-event'}
            ]
          }
        ]
      },
      {
        text: 'Examples',
        items: [
          {text: 'Using the Chat Window', link: '/examples/using-the-chat-window'},
          {
            text: 'Custom Tools',
            items: [
              {text: 'Send An Email',   link: '/examples/send-an-email'},
              {text: 'Add To Calendar', link: '/examples/add-to-calendar'}
            ]
          }
        ]
      },
      {
        text: 'Guides',
        items: [
          {
            items: [
              {text: 'Best Practices for Using AI in Craft CMS', link: '/guides/#best-practices-for-using-ai-in-craft-cms'},
              {text: 'Using the Clear Conversation Feature', link: '/guides/#using-the-clear-conversation-feature'}
            ]
          }
        ]
      }
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
                {text: 'Install via CLI',          link: '/getting-started/#installation-via-console-commands'}
              ]
            },
            {text: 'Settings Page', link: '/getting-started/settings'},
            {text: 'PHP Config File', link: '/getting-started/config'}
          ]
        }
      ],

      // Features
      '/features/': [
        {
          text: 'Features',
          items: [
            {text: 'Overview', link: '/features/'},
            {text: 'Chat Window', link: '/features/chat-window'},
            {text: 'Define Extra Tools Event', link: '/features/define-extra-tools-event'}
          ]
        }
      ],

      // Custom Tools
      '/custom-tools/': [
      ],

      // Examples
      '/examples/': [
        {
          text: 'Examples',
          items: [
            {text: 'Overview', link: '/examples/'},
            {text: 'Using the Chat Window', link: '/examples/using-the-chat-window'},
            {
              text: 'Custom Tools',
              items: [
                {text: 'Send An Email', link: '/examples/send-an-email'},
                {text: 'Add To Calendar', link: '/examples/add-to-calendar'}
              ]
            }
          ]
        }
      ],

      // Guides
      '/guides/': [
        {
          text: 'Guides',
          items: [
            {text: 'Best Practices for Using AI in Craft CMS', link: '/guides/#best-practices-for-using-ai-in-craft-cms'},
            {text: 'Using the Clear Conversation Feature', link: '/guides/#using-the-clear-conversation-feature'}
          ]
        }
      ]
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
