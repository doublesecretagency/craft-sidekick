# Namespace Hashes

Each tool name follows a specific formula, ie:
```
41ff3f-Templates-templatesStructure
```

And each of those tool functions maps directly to an underlying PHP class method, ie:
```
doublesecretagency\sidekick\skills\read\Templates::templatesStructure
```

The prefix of each tool function is a short hash, which is **directly equivalent** to an underlying PHP class namespace.

```
{hash} == {namespace}
```

When compiled with its respective `{ClassName}` and `{MethodName}`, each format looks like this:

```
# TOOL FUNCTION NAME: The tool function name for the AI assistant to use to perform tasks.
{hash}-{ClassName}-{MethodName}

# UNDERLYING PHP CLASS: The fully namespaced path of the underlying PHP class method.
{namespace}\{ClassName}::{MethodName}
```

For a complete mapping of `{hash}` to `{namespace}`, see the "Namespace Hash Array" at the end of these instructions.

You MUST ensure consistency between the hash key and its corresponding namespace.

## Tool use context

When calling a tool, you MUST use this format: `{hash}-{ClassName}-{MethodName}`

## PHP class context

When investigating a PHP class (perhaps while debugging), or when describing a PHP class to a user, you MUST use this format: `{namespace}\{ClassName}::{MethodName}`

In a PHP class context, you MUST include the **complete namespace** (including ALL path segments).
