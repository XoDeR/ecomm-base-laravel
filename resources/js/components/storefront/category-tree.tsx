import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import type { CategoryNode } from '@/types';

function containsActive(node: CategoryNode, activeId?: number): boolean {
    return (
        node.id === activeId ||
        node.children.some((child) => containsActive(child, activeId))
    );
}

function TreeNode({
    node,
    activeId,
    depth,
}: {
    node: CategoryNode;
    activeId?: number;
    depth: number;
}) {
    const [open, setOpen] = useState(containsActive(node, activeId));
    const active = node.id === activeId;

    return (
        <li>
            <div className="flex items-center">
                <Link
                    href={node.url}
                    style={{ paddingLeft: 12 + depth * 16 }}
                    className={`flex-1 rounded-md py-1.5 pr-2 text-sm hover:bg-blue-50 ${
                        active ? 'bg-blue-50 font-semibold text-blue-700' : 'text-slate-700'
                    }`}
                >
                    {node.name}
                </Link>
                {node.children.length > 0 && (
                    <button
                        type="button"
                        aria-label="Toggle subcategories"
                        aria-expanded={open}
                        onClick={() => setOpen((value) => !value)}
                        className="p-1.5 text-slate-500 hover:text-blue-600"
                    >
                        <ChevronRight
                            className={`size-4 transition-transform ${open ? 'rotate-90' : ''}`}
                        />
                    </button>
                )}
            </div>
            {open && node.children.length > 0 && (
                <ul>
                    {node.children.map((child) => (
                        <TreeNode
                            key={child.id}
                            node={child}
                            activeId={activeId}
                            depth={depth + 1}
                        />
                    ))}
                </ul>
            )}
        </li>
    );
}

export function CategoryTree({
    nodes,
    activeId,
}: {
    nodes: CategoryNode[];
    activeId?: number;
}) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4">
            <h3 className="mb-3 text-lg font-semibold">Categories</h3>
            {nodes.length === 0 ? (
                <p className="text-sm text-slate-500">No categories found.</p>
            ) : (
                <ul>
                    {nodes.map((node) => (
                        <TreeNode key={node.id} node={node} activeId={activeId} depth={0} />
                    ))}
                </ul>
            )}
        </div>
    );
}
