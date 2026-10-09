import React from 'react';

interface MarkdownRendererProps {
  content: string;
  className?: string;
}

/**
 * Lightweight, safe and aesthetically styled Markdown renderer for luxury cosmetic products.
 * Handles headings, bold, italic, bullet lists, numbered lists, blockquotes, and paragraphs.
 */
export default function MarkdownRenderer({ content, className = '' }: MarkdownRendererProps) {
  if (!content || typeof content !== 'string') {
    return null;
  }

  // Parse lines
  const lines = content.split('\n');
  const elements: React.ReactNode[] = [];
  let currentList: { type: 'ul' | 'ol'; items: string[] } | null = null;
  let keyCounter = 0;

  const flushList = () => {
    if (!currentList) return;
    const listKey = `list-${keyCounter++}`;
    if (currentList.type === 'ul') {
      elements.push(
        <ul key={listKey} className="space-y-2 my-3 pl-1">
          {currentList.items.map((item, idx) => (
            <li key={idx} className="flex items-start gap-2.5 text-xs text-[#57534E]">
              <span className="w-1.5 h-1.5 rounded-full bg-[#C5A880] mt-1.5 shrink-0" />
              <span>{formatInlineMarkdown(item)}</span>
            </li>
          ))}
        </ul>
      );
    } else {
      elements.push(
        <ol key={listKey} className="space-y-2 my-3 pl-1">
          {currentList.items.map((item, idx) => (
            <li key={idx} className="flex items-start gap-2.5 text-xs text-[#57534E]">
              <span className="w-5 h-5 rounded-full bg-stone-100 font-mono text-[10px] font-bold text-[#1C1917] flex items-center justify-center shrink-0">
                {idx + 1}
              </span>
              <span className="pt-0.5">{formatInlineMarkdown(item)}</span>
            </li>
          ))}
        </ol>
      );
    }
    currentList = null;
  };

  for (let i = 0; i < lines.length; i++) {
    const rawLine = lines[i];
    const trimmed = rawLine.trim();

    if (!trimmed) {
      flushList();
      continue;
    }

    // Heading 1 & 2 (## Title)
    if (trimmed.startsWith('## ')) {
      flushList();
      elements.push(
        <h3 key={`h2-${keyCounter++}`} className="font-serif text-lg sm:text-xl font-bold text-[#1C1917] mt-6 mb-2 first:mt-0 flex items-center gap-2">
          <span>{trimmed.replace(/^##\s+/, '')}</span>
        </h3>
      );
      continue;
    }

    // Heading 3 (### Subtitle)
    if (trimmed.startsWith('### ')) {
      flushList();
      elements.push(
        <h4 key={`h3-${keyCounter++}`} className="text-xs font-bold uppercase tracking-wider text-[#96674c] mt-4 mb-1.5">
          {trimmed.replace(/^###\s+/, '')}
        </h4>
      );
      continue;
    }

    // Heading 4 or 1 (# or ####)
    if (trimmed.startsWith('# ') || trimmed.startsWith('#### ')) {
      flushList();
      elements.push(
        <h4 key={`h4-${keyCounter++}`} className="font-serif text-base font-bold text-[#1C1917] mt-4 mb-1">
          {trimmed.replace(/^#{1,4}\s+/, '')}
        </h4>
      );
      continue;
    }

    // Blockquote (> Tip / Quote)
    if (trimmed.startsWith('> ')) {
      flushList();
      const quoteText = trimmed.replace(/^>\s+/, '');
      elements.push(
        <div key={`quote-${keyCounter++}`} className="my-3 p-4 bg-amber-50/70 rounded-2xl border border-amber-200/70 text-xs text-amber-950 flex items-start gap-3">
          <i className="fa-solid fa-lightbulb text-amber-600 text-sm mt-0.5 shrink-0" />
          <div className="leading-relaxed">
            {formatInlineMarkdown(quoteText)}
          </div>
        </div>
      );
      continue;
    }

    // Bullet List Item (- item or * item)
    if (/^[-*]\s+/.test(trimmed)) {
      const itemText = trimmed.replace(/^[-*]\s+/, '');
      if (!currentList || currentList.type !== 'ul') {
        flushList();
        currentList = { type: 'ul', items: [] };
      }
      currentList.items.push(itemText);
      continue;
    }

    // Numbered List Item (1. item)
    if (/^\d+\.\s+/.test(trimmed)) {
      const itemText = trimmed.replace(/^\d+\.\s+/, '');
      if (!currentList || currentList.type !== 'ol') {
        flushList();
        currentList = { type: 'ol', items: [] };
      }
      currentList.items.push(itemText);
      continue;
    }

    // Regular paragraph
    flushList();
    elements.push(
      <p key={`p-${keyCounter++}`} className="text-xs sm:text-sm text-[#2C2A29] leading-relaxed my-2">
        {formatInlineMarkdown(trimmed)}
      </p>
    );
  }

  flushList();

  return <div className={`space-y-1 ${className}`}>{elements}</div>;
}

/**
 * Format inline Markdown tags: **bold**, *italic*, `code`
 */
function formatInlineMarkdown(text: string): React.ReactNode {
  if (!text) return null;

  // Split by bold (**bold**), italic (*italic*), and inline code (`code`)
  const parts: React.ReactNode[] = [];
  const regex = /(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/g;
  let lastIndex = 0;
  let match: RegExpExecArray | null;

  while ((match = regex.exec(text)) !== null) {
    if (match.index > lastIndex) {
      parts.push(text.substring(lastIndex, match.index));
    }

    const token = match[0];
    if (token.startsWith('**') && token.endsWith('**')) {
      parts.push(
        <strong key={match.index} className="font-bold text-[#1C1917]">
          {token.slice(2, -2)}
        </strong>
      );
    } else if (token.startsWith('*') && token.endsWith('*')) {
      parts.push(
        <em key={match.index} className="italic text-[#78716C]">
          {token.slice(1, -1)}
        </em>
      );
    } else if (token.startsWith('`') && token.endsWith('`')) {
      parts.push(
        <code key={match.index} className="px-1.5 py-0.5 font-mono text-[11px] bg-stone-100 rounded text-stone-800 border border-stone-200">
          {token.slice(1, -1)}
        </code>
      );
    }

    lastIndex = match.index + token.length;
  }

  if (lastIndex < text.length) {
    parts.push(text.substring(lastIndex));
  }

  return parts.length > 0 ? parts : text;
}
