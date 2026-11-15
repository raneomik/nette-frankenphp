import type { HLJSApi, Language, Mode } from 'highlight.js';

// shortcut: grammaire minimale ({tags}, {$variables}, filtres, chaînes, commentaires) sur fond HTML ;
// les n:attributs sont colorés comme de simples attributs HTML, à étendre si les exemples en ont besoin
export default function latte(hljs: HLJSApi): Language {
    const expression: Mode[] = [
        hljs.APOS_STRING_MODE,
        hljs.QUOTE_STRING_MODE,
        hljs.C_NUMBER_MODE,
        { scope: 'variable', match: /\$\w+/ },
        { match: [/\|/, /\w+/], scope: { 2: 'title.function' } }, // |filtre
        { match: [/\w+/, /(?=\()/], scope: { 1: 'title.function' } }, // fonction(
        { scope: 'attr', match: /\w+(?=:)/ }, // argument nommé
    ];

    return {
        name: 'Latte',
        subLanguage: 'xml',
        contains: [
            hljs.COMMENT(/\{\*/, /\*\}/),
            { // {tag …} et {/tag}
                scope: 'template-tag',
                begin: [/\{\/?/, /[a-zA-Z][\w:]*/],
                beginScope: { 2: 'name' },
                end: /\}/,
                contains: expression,
            },
            { // {$var}, {=expr}, {_'clé'}, {!…}, {(…)} ; "{" suivi d'une espace n'est pas un tag
                scope: 'template-variable',
                begin: /\{(?=[$=_!(])/,
                end: /\}/,
                contains: expression,
            },
        ],
    };
}
