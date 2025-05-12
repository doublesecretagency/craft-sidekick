---
title: "Example: Explain Architecture | Sidekick plugin for Craft CMS"
description: "An example of a custom prompt to explain the nuances of your project architecture."
---

# Example: Explain Architecture

Feel free to explain your project structure in greater detail. You can fill in nuanced details which may not be evident just by looking at the projects templates and architecture.

```markdown
# My Example Project Architecture

This website is a film archive. The primary channel is of course "Films".

We also have a "Companies" channel. Each Film is related to a single Company
(via the "Production Company" field). Companies can be related to multiple Films.

Each User account has an "Employer" field, which is also a relation to the Company channel.
Since multiple Users can share the same Company, they can update the same Films and Company info.
```

## How to Add Prompts

Prompts can be as long or complex as you want. See the [`AddPromptsEvent`](/customize/add-prompts) for more detailed instructions.
