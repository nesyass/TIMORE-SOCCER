import React from 'react';
import { createRoot } from 'react-dom/client';
import { Bar, BarChart, CartesianGrid, LabelList, XAxis, YAxis } from 'recharts';
import { ChartContainer, ChartTooltip, ChartTooltipContent } from './shadcn/chart';

const config = {
    total: { label: 'Pendaftaran', color: 'var(--chart-bar)' },
};

const root = document.querySelector('#registration-chart');
if (root) {
    const entries = root.parentElement.querySelectorAll('[data-trend-label]');
    const data = Array.from(entries, (entry) => ({
        label: entry.dataset.trendLabel,
        total: Number(entry.dataset.trendTotal),
    }));

    createRoot(root).render(
        <ChartContainer config={config} className="registration-chart-frame" initialDimension={{ width: 300, height: 156 }}>
            <BarChart accessibilityLayer data={data} title="Jumlah pendaftaran per bulan" margin={{ top: 18, right: 8, left: 8, bottom: 0 }}>
                <CartesianGrid vertical={false} stroke="var(--border)" />
                <XAxis dataKey="label" axisLine={false} tickLine={false} tickMargin={8} minTickGap={8} tick={{ fill: 'var(--text-muted)', fontSize: 11 }} />
                <YAxis hide allowDecimals={false} domain={[0, (maximum) => Math.max(1, maximum)]} />
                <ChartTooltip cursor={{ fill: 'var(--surface-soft)' }} content={<ChartTooltipContent className="registration-chart-tooltip" hideIndicator />} />
                <Bar dataKey="total" fill="var(--color-total)" radius={[4, 4, 0, 0]} maxBarSize={48} isAnimationActive={false}>
                    <LabelList dataKey="total" position="top" fill="var(--text)" fontSize={11} />
                </Bar>
            </BarChart>
        </ChartContainer>
    );
}