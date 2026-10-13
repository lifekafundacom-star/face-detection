<?php

use Faker\Core\Color;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
use dosamigos\ckeditor\CKEditor;

/** @var yii\web\View $this */
/** @var common\models\Product $model */
/** @var yii\widgets\ActiveForm $form */
$selectedText = [
    'text' => '',
    'textalign' => '',
    'fontstyle' => '',
    'color' => '#ff0000',
    'fontweight' => '',
    'x' => '',
    'y' => '',
    'fontsize' => '',
    'fontfamily' => '',
    'width' => '',
    'height' => '',
    'rotate' => '0',
];

if (is_array($data)) {
    foreach ($data as $item) {
        if (isset($item['type']) && $item['type'] === 'text') {
            $selectedText = array_merge($selectedText, $item);
            break;
        }
    }
}

$previewImageData = '';
if (isset($model) && $model->hasAttribute('show_image')) {
    $previewImageData = trim((string)$model->show_image);
}
if ($previewImageData === '') {
    $previewImageData = (string)$imageData;
}
?>
<link href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.min.css" rel="stylesheet" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400;500&display=swap');

    .note {
        box-sizing: border-box;
        display: block;
        font-family: cursive;
        text-transform: none;
        font-size: 25px;
        line-height: .9;
        margin: 0;
        min-width: 1px;
        max-width: none;
        overflow: hidden;
        overflow-wrap: anywhere;
        padding: 0;
        white-space: normal;
        word-break: break-word;
    }

    .layer-card {
        background: #ffffff;
        border: 1px solid #dbe3ec;
        border-radius: 6px;
        cursor: pointer;
        display: block;
        list-style: none;
        margin: 0 0 10px;
        padding: 10px 12px;
        text-align: left;
        width: 100%;
    }

    .layer-card:hover,
    .layer-card.is-selected {
        background: #eef6ff;
        border-color: #2563eb;
    }

    .layer-card strong {
        color: #172033;
        display: block;
        font-size: 13px;
        margin-bottom: 5px;
    }

    .layer-card span {
        color: #64748b;
        display: block;
        font-size: 11px;
        line-height: 1.45;
    }

    .note.is-selected-layer,
    .overlay.is-selected-layer,
    .element-layer.is-selected-layer,
    .template-background-layer.is-selected-layer {
        background-color: rgba(245, 158, 11, 0.18) !important;
        box-shadow: 0 0 0 3px #f59e0b, 0 0 0 7px rgba(245, 158, 11, 0.25);
        opacity: .78;
        z-index: 10000 !important;
    }

    .note.is-selected-layer {
        border-color: #f59e0b !important;
    }

    .overlay.is-selected-layer,
    .element-layer.is-selected-layer {
        border-color: #f59e0b !important;
    }

    .element-layer {
        align-items: center;
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        min-height: 1px;
        min-width: 1px;
        position: absolute;
    }

    .element-layer[data-element-type="circle"] {
        border-radius: 50%;
    }

    .element-layer[data-element-type="line"] {
        height: 4px !important;
        min-height: 1px;
    }

    .element-layer[data-element-type="star"] {
        clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
    }

    .element-layer[data-element-type="svg"],
    .element-layer[data-element-type="png"] {
        font-size: 38px;
        line-height: 1;
    }

    .layer-card-head {
        align-items: flex-start;
        display: flex;
        gap: 8px;
        justify-content: space-between;
    }

    .layer-card-actions {
        display: flex;
        gap: 4px;
    }

    .layer-card-actions button {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        color: #334155;
        font-size: 11px;
        font-weight: 700;
        height: 22px;
        line-height: 1;
        padding: 0 6px;
    }

    .layer-card-actions button:disabled {
        color: #94a3b8;
        cursor: not-allowed;
    }

    .selected-layer-info {
        background: #f8fafc;
        border: 1px solid #dbe3ec;
        border-radius: 6px;
        margin-bottom: 12px;
        padding: 10px 12px;
    }

    .selected-layer-info strong {
        color: #172033;
        display: block;
        font-size: 13px;
        margin-bottom: 4px;
    }

    .selected-layer-info span {
        color: #64748b;
        display: block;
        font-size: 11px;
        line-height: 1.45;
    }

    .delete-layer-btn {
        background: #dc2626;
        border: 0;
        border-radius: 4px;
        color: #ffffff;
        display: none;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .04em;
        margin: 0 0 14px;
        padding: 8px 12px;
        text-transform: uppercase;
        width: 100%;
    }

    .delete-layer-btn:disabled {
        background: #cbd5e1;
        cursor: not-allowed;
    }

    .canvas-scale-toolbar {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #dbe3ec;
        border-radius: 6px;
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        padding: 8px 10px;
    }

    .canvas-scale-toolbar label,
    .canvas-scale-toolbar span {
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        margin: 0;
        white-space: nowrap;
    }

    .canvas-scale-toolbar input[type="range"] {
        flex: 1;
    }

    .canvas-overlay-option {
        align-items: center;
        display: inline-flex;
        gap: 4px;
    }

    .canvas-overlay-option input {
        margin: 0;
    }

    .canvas-viewport {
        background: #eef2f7;
        border: 1px solid #dbe3ec;
        height: 640px;
        overflow: auto;
        width: 100%;
    }

    .canvas-scale-shell {
        padding-left: 32px;
        padding-top: 24px;
        position: relative;
    }

    .canvas-grid-overlay,
    .canvas-guide-line,
    .canvas-ruler {
        pointer-events: none;
        position: absolute;
    }

    .canvas-grid-overlay {
        background-image:
            linear-gradient(rgba(37, 99, 235, .28) 1px, transparent 1px),
            linear-gradient(90deg, rgba(37, 99, 235, .28) 1px, transparent 1px),
            linear-gradient(rgba(15, 23, 42, .18) 1px, transparent 1px),
            linear-gradient(90deg, rgba(15, 23, 42, .18) 1px, transparent 1px);
        display: none;
        left: 32px;
        top: 24px;
        z-index: 9000;
    }

    .canvas-guide-line {
        background: #ef4444;
        display: none;
        z-index: 10050;
    }

    .canvas-guide-line.vertical {
        top: 24px;
        width: 1px;
    }

    .canvas-guide-line.horizontal {
        height: 1px;
        left: 32px;
    }

    .canvas-ruler {
        background: rgba(248, 250, 252, .92);
        border: 1px solid #cbd5e1;
        color: #334155;
        display: none;
        font-size: 10px;
        overflow: hidden;
        z-index: 10060;
    }

    .canvas-ruler-top {
        height: 24px;
        left: 32px;
        top: 0;
    }

    .canvas-ruler-left {
        left: 0;
        top: 24px;
        width: 32px;
    }

    .ruler-tick {
        background: #64748b;
        position: absolute;
    }

    .canvas-ruler-top .ruler-tick {
        bottom: 0;
        width: 1px;
    }

    .canvas-ruler-left .ruler-tick {
        height: 1px;
        right: 0;
    }

    .ruler-label {
        color: #334155;
        position: absolute;
        white-space: nowrap;
    }

    .canvas-scale-target,
    .xyz,
    .xyz1 {
        background-image: none;
        position: relative;
    }

    .template-background-layer {
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
        bottom: 0;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
    }

    .font-color-picker {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        height: 34px;
        padding: 2px;
        width: 100%;
    }

    .quick-access-panel {
        background: #f8fafc;
        border-left: 1px solid #e2e8f0;
        max-height: calc(100vh - 150px);
        overflow-y: auto;
        padding: 6px;
    }

    .quick-access-panel .selected-layer-info {
        margin-bottom: 8px;
    }

    .quick-access-panel .delete-layer-btn {
        margin-bottom: 10px;
    }

    .quick-tool-section {
        background: var(--tool-bg, #ffffff);
        border: 1px solid var(--tool-border, #dbe3ec);
        border-radius: 6px;
        margin: 0 0 8px;
        padding: 7px;
    }

    .quick-tool-section h1 {
        align-items: center;
        background: var(--tool-head-bg, #eef2ff);
        border: 1px solid var(--tool-border, #dbe3ec);
        border-radius: 4px;
        color: var(--tool-head-color, #172033);
        display: flex;
        font-family: var(--tool-font, Arial, sans-serif);
        font-size: 12px;
        font-weight: 800;
        height: 24px;
        justify-content: space-between;
        letter-spacing: .03em;
        line-height: 1;
        margin: 0 0 5px;
        padding: 0 7px;
        text-transform: uppercase;
    }

    .quick-tool-section h1::after {
        color: var(--tool-head-muted, #64748b);
        content: "tools";
        font-size: 9px;
        font-weight: 600;
        letter-spacing: 0;
        text-transform: none;
    }

    .quick-tool-section .row {
        display: grid;
        gap: 6px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin: 0 0 7px;
    }

    .quick-tool-section .row::before,
    .quick-tool-section .row::after {
        content: none;
        display: none;
    }

    .quick-tool-section .row:last-child {
        margin-bottom: 0;
    }

    .quick-tool-section [class^="col-md-"],
    .quick-tool-section [class*=" col-md-"] {
        float: none;
        min-height: 0;
        padding-left: 0;
        padding-right: 0;
        width: auto;
    }

    .quick-tool-section .col-md-12 {
        grid-column: 1 / -1;
    }

    .quick-tool-section .col-md-6 {
        grid-column: span 1;
    }

    .quick-tool-section .bt.col-md-6 {
        grid-column: span 1;
    }

    .quick-tool-section .slt,
    .quick-tool-section input[type="text"],
    .quick-tool-section input[type="number"],
    .quick-tool-section input[type="color"],
    .quick-tool-section select {
        background: #ffffff;
        border: 1px solid var(--tool-control-border, #cbd5e1);
        border-radius: 4px;
        color: #172033;
        font-family: var(--tool-font, Arial, sans-serif);
        font-size: 10px;
        height: 24px;
        padding: 2px 5px;
        width: 100%;
    }

    .quick-tool-section label {
        color: var(--tool-label-color, #475569);
        display: block;
        font-family: var(--tool-font, Arial, sans-serif);
        font-size: 10px;
        font-weight: 700;
        height: auto;
        line-height: 1.2;
        margin: 0 0 3px;
    }

    .quick-tool-section .tool-field {
        align-self: end;
        align-items: center;
        display: grid;
        gap: 4px;
        grid-template-columns: minmax(44px, .8fr) minmax(40px, 1fr);
    }

    .quick-tool-section .col-md-6:has(> label:not(.reuse-upload-option)),
    .quick-tool-section .col-md-12:has(> label:not(.reuse-upload-option)) {
        align-items: center;
        display: grid;
        gap: 4px;
        grid-template-columns: minmax(44px, .8fr) minmax(40px, 1fr);
    }

    .quick-tool-section .col-md-6:has(> label:not(.reuse-upload-option)) {
        grid-template-columns: minmax(44px, .8fr) minmax(40px, 1fr);
    }

    .quick-tool-section .tool-field label {
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .quick-tool-section .col-md-6:has(> label:not(.reuse-upload-option)) > label,
    .quick-tool-section .col-md-12:has(> label:not(.reuse-upload-option)) > label {
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .quick-tool-section .col-md-6.tool-field {
        grid-template-columns: minmax(44px, .8fr) minmax(40px, 1fr);
    }

    .quick-tool-section .tool-field > input,
    .quick-tool-section .tool-field > select,
    .quick-tool-section .tool-field > button,
    .quick-tool-section .tool-field > .format-toggle,
    .quick-tool-section .tool-field > .font-color-picker,
    .quick-tool-section .tool-field > .reuse-image-picker {
        min-width: 0;
    }

    .quick-tool-section .tool-action {
        align-self: end;
    }

    .quick-tool-section .tool-check {
        align-items: center;
        background: rgba(255, 255, 255, .6);
        border: 1px solid var(--tool-border, #dbe3ec);
        border-radius: 4px;
        display: flex;
        gap: 6px;
        min-height: 28px;
        padding: 4px 6px;
    }

    .quick-tool-section .font-color-picker {
        height: 24px;
        min-height: 24px;
        padding: 1px;
    }

    .quick-tool-section .btn {
        background-color: var(--tool-button-bg, #2563eb);
        border-color: var(--tool-button-border, #1d4ed8);
        border-radius: 4px;
        color: var(--tool-button-color, #ffffff);
        font-family: var(--tool-font, Arial, sans-serif);
        font-size: 9px;
        font-weight: 700;
        letter-spacing: 0;
        min-height: 24px;
        padding: 3px 5px;
        text-transform: uppercase;
        white-space: normal;
        width: 100%;
    }

    .font-size-tools {
        align-items: center;
        display: grid;
        gap: 4px;
        grid-template-columns: minmax(44px, .8fr) repeat(2, minmax(26px, 1fr));
    }

    .font-size-tools label {
        margin: 0;
    }

    .font-size-tools .font-size-step {
        background: #e2e8f0;
        border: 1px solid #cbd5e1;
        color: #172033;
        font-size: 13px;
        line-height: 1;
        min-height: 24px;
        padding: 2px 4px;
        text-transform: none;
    }

    #fontstyle,
    #fontweight {
        display: none;
    }

    .format-toggle {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        color: #64748b;
        font-size: 14px;
        height: 24px;
        line-height: 1;
        padding: 2px 6px;
        width: 100%;
    }

    .format-toggle.is-active {
        background: #172033;
        border-color: #172033;
        color: #ffffff;
    }

    #fontBoldToggle.is-active {
        font-weight: 900;
    }

    #fontBoldToggle:not(.is-active) {
        font-weight: 400;
    }

    #fontItalicToggle.is-active {
        font-style: italic;
        font-weight: 700;
    }

    #fontItalicToggle:not(.is-active) {
        font-style: normal;
        font-weight: 400;
    }

    .quick-tool-section .bt {
        display: flex;
    }

    .quick-tool-elements .add-element {
        background: #f59e0b;
        border-color: #d97706;
        color: #ffffff;
    }

    .reuse-upload-option {
        align-items: center;
        display: flex;
        gap: 4px;
        height: 28px;
        margin: 0;
    }

    .reuse-upload-option input {
        margin: 0;
    }

    #reusableLayerImageSelect {
        display: none;
    }

    .reuse-image-picker {
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        display: grid;
        gap: 6px;
        grid-template-columns: 42px minmax(0, 1fr) 58px;
        min-height: 46px;
        padding: 4px;
        width: 100%;
    }

    .quick-tool-text {
        --tool-bg: #fff7ed;
        --tool-border: #fed7aa;
        --tool-head-bg: #9a3412;
        --tool-head-color: #fff7ed;
        --tool-head-muted: #ffedd5;
        --tool-control-border: #fb923c;
        --tool-label-color: #7c2d12;
        --tool-button-bg: #c2410c;
        --tool-button-border: #9a3412;
        --tool-button-color: #ffffff;
        --tool-font: Georgia, "Times New Roman", serif;
    }

    .quick-tool-image {
        --tool-bg: #eff6ff;
        --tool-border: #bfdbfe;
        --tool-head-bg: #1d4ed8;
        --tool-head-color: #eff6ff;
        --tool-head-muted: #dbeafe;
        --tool-control-border: #60a5fa;
        --tool-label-color: #1e3a8a;
        --tool-button-bg: #2563eb;
        --tool-button-border: #1d4ed8;
        --tool-button-color: #ffffff;
        --tool-font: Arial, Helvetica, sans-serif;
    }

    .quick-tool-upload-image {
        --tool-bg: #f0fdf4;
        --tool-border: #bbf7d0;
        --tool-head-bg: #15803d;
        --tool-head-color: #f0fdf4;
        --tool-head-muted: #dcfce7;
        --tool-control-border: #4ade80;
        --tool-label-color: #14532d;
        --tool-button-bg: #16a34a;
        --tool-button-border: #15803d;
        --tool-button-color: #ffffff;
        --tool-font: "Trebuchet MS", Arial, sans-serif;
    }

    .quick-tool-elements {
        --tool-bg: #faf5ff;
        --tool-border: #e9d5ff;
        --tool-head-bg: #7e22ce;
        --tool-head-color: #faf5ff;
        --tool-head-muted: #f3e8ff;
        --tool-control-border: #c084fc;
        --tool-label-color: #581c87;
        --tool-button-bg: #9333ea;
        --tool-button-border: #7e22ce;
        --tool-button-color: #ffffff;
        --tool-font: Verdana, Arial, sans-serif;
    }

    @media (max-width: 1200px) {
        .quick-tool-section .row {
            grid-template-columns: 1fr;
        }

        .quick-tool-section .col-md-6,
        .quick-tool-section .bt.col-md-6 {
            grid-column: 1 / -1;
        }
    }

    .reuse-image-thumb {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 3px;
        height: 36px;
        object-fit: cover;
        width: 36px;
    }

    .reuse-image-empty {
        align-items: center;
        color: #94a3b8;
        display: flex;
        font-size: 9px;
        justify-content: center;
        text-align: center;
    }

    .reuse-image-name {
        color: #172033;
        display: block;
        font-size: 10px;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .reuse-image-picker .btn {
        min-height: 26px;
        padding: 2px 4px;
    }

    .reuse-gallery-popup {
        align-items: center;
        background: rgba(15, 23, 42, .72);
        bottom: 0;
        display: none;
        justify-content: center;
        left: 0;
        padding: 24px;
        position: fixed;
        right: 0;
        top: 0;
        z-index: 20001;
    }

    .reuse-gallery-popup.is-open {
        display: flex;
    }

    .reuse-gallery-dialog {
        background: #ffffff;
        border-radius: 6px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .28);
        max-height: 86vh;
        max-width: 760px;
        padding: 14px;
        position: relative;
        width: 86vw;
    }

    .reuse-gallery-dialog h2 {
        color: #172033;
        font-size: 15px;
        font-weight: 800;
        margin: 0 34px 12px 0;
    }

    .reuse-gallery-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
        max-height: calc(86vh - 70px);
        overflow: auto;
        padding: 4px;
    }

    .reuse-gallery-item {
        background: #ffffff;
        border: 1px solid #dbe3ec;
        border-radius: 5px;
        cursor: pointer;
        min-height: 112px;
        padding: 6px;
        position: relative;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .reuse-gallery-item:hover,
    .reuse-gallery-item.is-selected {
        border-color: #2563eb;
        box-shadow: 0 8px 24px rgba(37, 99, 235, .18);
        z-index: 2;
    }

    .reuse-gallery-item img {
        background: #f8fafc;
        border-radius: 4px;
        display: block;
        height: 78px;
        object-fit: cover;
        transition: transform .18s ease, box-shadow .18s ease;
        width: 100%;
    }

    .reuse-gallery-item:hover img {
        box-shadow: 0 12px 30px rgba(15, 23, 42, .28);
        transform: scale(1.75);
        transform-origin: center;
    }

    .reuse-gallery-item span {
        color: #475569;
        display: block;
        font-size: 10px;
        margin-top: 6px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .reuse-gallery-empty {
        color: #64748b;
        font-size: 13px;
        grid-column: 1 / -1;
        padding: 22px;
        text-align: center;
    }

    .reuse-gallery-close {
        background: #172033;
        border: 0;
        border-radius: 50%;
        color: #ffffff;
        font-size: 18px;
        height: 30px;
        line-height: 28px;
        position: absolute;
        right: -12px;
        top: -12px;
        width: 30px;
    }

    .image-convert-popup {
        align-items: center;
        background: rgba(15, 23, 42, .72);
        bottom: 0;
        display: none;
        justify-content: center;
        left: 0;
        padding: 24px;
        position: fixed;
        right: 0;
        top: 0;
        z-index: 20002;
    }

    .image-convert-popup.is-open {
        display: flex;
    }

    .image-convert-dialog {
        background: #ffffff;
        border-radius: 6px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .28);
        max-height: 88vh;
        max-width: 820px;
        padding: 14px;
        position: relative;
        width: 88vw;
    }

    .image-convert-dialog h2 {
        color: #172033;
        font-size: 15px;
        font-weight: 800;
        margin: 0 34px 10px 0;
    }

    .image-convert-body {
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(0, 1fr) 220px;
    }

    .image-convert-preview {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #dbe3ec;
        border-radius: 5px;
        display: flex;
        justify-content: center;
        min-height: 280px;
        overflow: auto;
        padding: 10px;
    }

    .image-convert-preview canvas {
        background-image: linear-gradient(45deg, #e2e8f0 25%, transparent 25%), linear-gradient(-45deg, #e2e8f0 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #e2e8f0 75%), linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
        background-position: 0 0, 0 8px, 8px -8px, -8px 0;
        background-size: 16px 16px;
        max-height: 58vh;
        max-width: 100%;
    }

    .image-convert-tools {
        display: grid;
        gap: 8px;
    }

    .image-convert-tools label {
        color: #475569;
        display: grid;
        font-size: 11px;
        font-weight: 700;
        gap: 3px;
    }

    .image-convert-tools input {
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        color: #172033;
        font-size: 11px;
        height: 28px;
        padding: 3px 6px;
        width: 100%;
    }

    .image-convert-meta {
        color: #64748b;
        font-size: 11px;
        line-height: 1.5;
        word-break: break-word;
    }

    .image-convert-actions {
        display: grid;
        gap: 6px;
        grid-template-columns: 1fr 1fr;
        margin-top: 4px;
    }

    .image-convert-actions .btn {
        border-radius: 4px;
        font-size: 10px;
        font-weight: 800;
        min-height: 30px;
        text-transform: uppercase;
        width: 100%;
    }

    .image-convert-close {
        background: #172033;
        border: 0;
        border-radius: 50%;
        color: #ffffff;
        font-size: 18px;
        height: 30px;
        line-height: 28px;
        position: absolute;
        right: -12px;
        top: -12px;
        width: 30px;
    }

    @media (max-width: 760px) {
        .image-convert-body {
            grid-template-columns: 1fr;
        }
    }

    .product-image-preview {
        background: #ffffff;
        border: 1px solid #dbe3ec;
        border-radius: 6px;
        margin: 0 0 10px;
        padding: 8px;
    }

    .product-image-preview strong {
        color: #172033;
        display: block;
        font-size: 12px;
        margin-bottom: 6px;
    }

    .product-preview-thumb {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        cursor: pointer;
        display: block;
        height: 86px;
        object-fit: cover;
        width: 86px;
    }

    .product-preview-popup {
        align-items: center;
        background: rgba(15, 23, 42, .72);
        bottom: 0;
        display: none;
        justify-content: center;
        left: 0;
        padding: 24px;
        position: fixed;
        right: 0;
        top: 0;
        z-index: 20000;
    }

    .product-preview-popup.is-open {
        display: flex;
    }

    .product-preview-dialog {
        background: #ffffff;
        border-radius: 6px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, .28);
        max-height: 90vh;
        max-width: 90vw;
        padding: 12px;
        position: relative;
    }

    .product-preview-dialog img {
        display: block;
        max-height: calc(90vh - 24px);
        max-width: calc(90vw - 24px);
    }

    .product-preview-close {
        background: #172033;
        border: 0;
        border-radius: 50%;
        color: #ffffff;
        font-size: 18px;
        height: 30px;
        line-height: 28px;
        position: absolute;
        right: -12px;
        top: -12px;
        width: 30px;
        z-index: 10090;
    }

    .preview-image-container {
        padding-left: 32px;
        padding-top: 24px;
        position: relative;
        display: inline-block;
    }

    .preview-grid-overlay,
    .preview-ruler {
        pointer-events: none;
        position: absolute;
    }

    .preview-grid-overlay {
        background-image:
            linear-gradient(rgba(37, 99, 235, .28) 1px, transparent 1px),
            linear-gradient(90deg, rgba(37, 99, 235, .28) 1px, transparent 1px),
            linear-gradient(rgba(15, 23, 42, .18) 1px, transparent 1px),
            linear-gradient(90deg, rgba(15, 23, 42, .18) 1px, transparent 1px);
        left: 32px;
        top: 24px;
        z-index: 9000;
        display: none;
    }

    .preview-ruler {
        background: rgba(248, 250, 252, .92);
        border: 1px solid #cbd5e1;
        color: #334155;
        font-size: 10px;
        overflow: hidden;
        z-index: 10060;
        display: none;
    }

    .preview-ruler-top {
        height: 24px;
        left: 32px;
        top: 0;
    }

    .preview-ruler-left {
        left: 0;
        top: 24px;
        width: 32px;
    }

    .preview-ruler .ruler-tick {
        background: #64748b;
        position: absolute;
    }

    .preview-ruler-top .ruler-tick {
        bottom: 0;
        width: 1px;
    }

    .preview-ruler-left .ruler-tick {
        height: 1px;
        right: 0;
    }

    .preview-ruler .ruler-label {
        color: #334155;
        position: absolute;
        white-space: nowrap;
    }

</style>
<div class="main">
    <div class="product-form">
        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-md-3">
                <?= $form->field($model, 'template_id')->dropDownList($template_name, ['prompt' => 'Select Template Name', 'required' => true, 'readonly' => true])->label(false) ?>
                <?= $form->field($model, 'language_id')->dropDownList($language_name, ['prompt' => 'Select language', 'required' => true, 'readonly' => true, 'onchange' => 'get_images(this.value)'])->label(false) ?>
                <div class="product-image-preview">
                    <strong>Product image</strong>
                    <img src="<?= Yii::getAlias('@storageUrl') ?>/images/<?= Html::encode($previewImageData) ?>" alt="Product preview" class="product-preview-thumb" id="productPreviewThumb">
                </div>
    
                <div id="layerList">
                    <?php if ($data) { ?>
                        <?php foreach ($data as $item) {
                            if ($item['type'] == 'text') {
                        ?>
                                <button type="button" class="layer-card" data-layer-id="<?= Html::encode($item['id']) ?>" data-layer-type="text">
                                    <strong>Text layer: <?= Html::encode($item['text'] ?? 'Text') ?></strong>
                                    <span>X: <?= Html::encode($item['x'] ?? '') ?>, Y: <?= Html::encode($item['y'] ?? '') ?></span>
                                    <span>Color: <?= Html::encode($item['color'] ?? '') ?>, Size: <?= Html::encode($item['fontsize'] ?? '') ?>, Rotate: <?= Html::encode($item['rotate'] ?? 0) ?></span>
                                </button>
                            <?php } else { ?>
                                <button type="button" class="layer-card" data-layer-id="<?= Html::encode($item['id']) ?>" data-layer-type="image">
                                    <strong>Image layer: <?= Html::encode(strtoupper($item['place'] ?? 'image')) ?></strong>
                                    <span>X: <?= Html::encode($item['x'] ?? '') ?>, Y: <?= Html::encode($item['y'] ?? '') ?></span>
                                    <span><?= Html::encode($item['width'] ?? '') ?> x <?= Html::encode($item['height'] ?? '') ?> px, Scale: <?= Html::encode($item['scale'] ?? 100) ?>%, Rotate: <?= Html::encode($item['rotate'] ?? '') ?></span>
                                </button>
                    <?php  }
                        }
                    } ?>
                </div>

            </div>
            <div class="col-md-9">
                
                <div class="row">
                    <div class="col-md-9 pos">
                        <div class="canvas-scale-toolbar">
                            <label for="canvasScale">Canvas Scale</label>
                            <input type="range" id="canvasScale" min="40" max="150" value="60" step="5">
                            <span id="canvasScaleValue">60%</span>
                            <label class="canvas-overlay-option"><input type="checkbox" id="showCanvasGrid" checked> Grid</label>
                            <label class="canvas-overlay-option"><input type="checkbox" id="showCanvasRulers" checked> Ruler</label>
                            <label class="canvas-overlay-option"><input type="checkbox" id="showCanvasGuides" checked> Guides</label>
                        </div>
                        <div class="canvas-viewport" id="canvasViewport">
                            <div class="canvas-scale-shell" id="canvasScaleShell">
                                <div class="canvas-grid-overlay" id="canvasGridOverlay"></div>
                                <div class="canvas-ruler canvas-ruler-top" id="canvasRulerTop"></div>
                                <div class="canvas-ruler canvas-ruler-left" id="canvasRulerLeft"></div>
                                <div class="canvas-guide-line vertical" id="canvasGuideVertical"></div>
                                <div class="canvas-guide-line horizontal" id="canvasGuideHorizontal"></div>
                            <?php if ($templates->image_type == 'square') { ?>
                                <div class="xyz canvas-scale-target" style="height: 1000px; width:1000px;">
                                    <div class="template-background-layer" id="template-background" data-layer-type="background" data-background-url="<?= Yii::getAlias('@storageUrl') ?>/images/<?= Html::encode($imageData) ?>" style="background-image:url(&quot;<?= Yii::getAlias('@storageUrl') ?>/images/<?= Html::encode($imageData) ?>&quot;)"></div>
                                    <?php if ($data) { ?>
                                        <?php foreach ($data as $key => $value) { ?>
                                            <?php if ($value['type'] == 'text') { ?>
                                                <div class="note ui-draggable ui-draggable-handle unique-class" id="<?= $value['id'] ?>" data-layer-type="text" style="border:1px solid black; width:<?= $value['width'] ?? 0 ?>px; height:<?= $value['height'] ?? 0 ?>px; position: absolute; left: <?= $value['x'] ?? 0 ?>px; top: <?= $value['y'] ?? '' ?>px; text-align:<?= $value['textalign'] ?? '' ?>; color:<?= $value['color'] ?? '' ?>; font-style: <?= $value['fontstyle'] ?? '' ?>; font-weight: <?= $value['fontweight'] ?? '' ?>; font-size: <?= $value['fontsize'] ?? '' ?>px; font-family: <?= $value['fontfamily'] ?? '' ?>; transform: rotate(<?= $value['rotate'] ?? 0 ?>deg);"><?= $value['text'] ?>
                                                </div>
                                            <?php } ?>
                                            <?php if ($value['type'] == 'image') {
                                                $imagePlace = strtolower($value['place'] ?? '');
                                            ?>
                                                <div class="overlay ui-draggable ui-draggable-handle" id="<?= Html::encode($value['id']) ?>" data-layer-type="image" data-base-width="<?= Html::encode($value['baseWidth'] ?? $value['width'] ?? 100) ?>" data-base-height="<?= Html::encode($value['baseHeight'] ?? $value['height'] ?? 100) ?>" style="position: absolute; width: <?= $value['width'] ?? 100 ?>px; height: <?= $value['height'] ?? 100 ?>px; transform: rotate(<?= $value['rotate'] ?? 0 ?>deg); background-color: <?= !empty($value['image_src']) ? 'transparent' : 'rgba(0, 0, 0, 0.5)' ?>; background-image:<?= !empty($value['image_src']) ? 'url(&quot;' . Yii::getAlias('@storageUrl') . '/images/' . Html::encode($value['image_src']) . '&quot;)' : 'none' ?>; background-position:center center; background-repeat:no-repeat; background-size:cover; border: 2px solid <?= $imagePlace === 'fg' ? 'red' : 'white' ?>; left: <?= $value['x'] ?? 0 ?>px; top:<?= $value['y'] ?? 0 ?>px;">
                                                </div>
                                            <?php } ?>
                                            <?php if (($value['type'] ?? '') == 'element') { ?>
                                                <div class="element-layer ui-draggable ui-draggable-handle" id="<?= Html::encode($value['id']) ?>" data-layer-type="element" data-element-type="<?= Html::encode($value['element_type'] ?? 'rectangle') ?>" style="position:absolute; left:<?= $value['x'] ?? 0 ?>px; top:<?= $value['y'] ?? 0 ?>px; width:<?= $value['width'] ?? 120 ?>px; height:<?= $value['height'] ?? 120 ?>px; transform:rotate(<?= $value['rotation'] ?? $value['rotate'] ?? 0 ?>deg); background-color:<?= Html::encode($value['color'] ?? '#60a5fa') ?>; opacity:<?= Html::encode($value['opacity'] ?? 1) ?>; border:<?= Html::encode($value['border_width'] ?? 2) ?>px solid <?= Html::encode($value['border_color'] ?? '#1d4ed8') ?>;"><?= in_array(($value['element_type'] ?? ''), ['svg', 'png'], true) ? Html::encode($value['element_type'] === 'svg' ? 'SVG' : 'PNG') : '' ?></div>
                                            <?php } ?>

                                    <?php }
                                    } ?>
                                </div>
                            <?php } else { ?>
                                <div class="xyz1 canvas-scale-target" style="height: 1000px; width:800px;">
                                    <div class="template-background-layer" id="template-background" data-layer-type="background" data-background-url="<?= Yii::getAlias('@storageUrl') ?>/images/<?= Html::encode($imageData) ?>" style="background-image:url(&quot;<?= Yii::getAlias('@storageUrl') ?>/images/<?= Html::encode($imageData) ?>&quot;)"></div>
                                    <?php if ($data) { ?>
                                        <?php foreach ($data as $key => $value) { ?>
                                            <?php if ($value['type'] == 'text') { ?>
                                                <div class="note ui-draggable ui-draggable-handle unique-class" id="<?= $value['id'] ?>" data-layer-type="text" style="border:1px solid yellow; width:<?= $value['width'] ?? 0 ?>px; height:<?= $value['height'] ?? 0 ?>px;  position: absolute; left: <?= $value['x'] ?? 0 ?>px; top: <?= $value['y'] ?? 0 ?>px; text-align:<?= $value['textalign'] ?? '' ?>; color:<?= $value['color'] ?? '' ?>; font-style: <?= $value['fontstyle'] ?? '' ?>; font-weight: <?= $value['fontweight'] ?? '' ?>; font-size: <?= $value['fontsize']  ?? '' ?>px; font-family: <?= $value['fontfamily'] ?? '' ?>; transform: rotate(<?= $value['rotate'] ?? 0 ?>deg);"><?= $value['text'] ?>
                                                </div>
                                            <?php } ?>
                                            <?php if ($value['type'] == 'image') {
                                                $imagePlace = strtolower($value['place'] ?? '');
                                            ?>
                                                <div class="overlay ui-draggable ui-draggable-handle" id="<?= Html::encode($value['id']) ?>" data-layer-type="image" data-base-width="<?= Html::encode($value['baseWidth'] ?? $value['width'] ?? 100) ?>" data-base-height="<?= Html::encode($value['baseHeight'] ?? $value['height'] ?? 100) ?>" style="position: absolute; width: <?= $value['width'] ?? 100 ?>px; height: <?= $value['height'] ?? '100' ?>px; transform: rotate(<?= $value['rotate'] ?? 0 ?>deg); background-color: <?= !empty($value['image_src']) ? 'transparent' : 'rgba(0, 0, 0, 0.5)' ?>; background-image:<?= !empty($value['image_src']) ? 'url(&quot;' . Yii::getAlias('@storageUrl') . '/images/' . Html::encode($value['image_src']) . '&quot;)' : 'none' ?>; background-position:center center; background-repeat:no-repeat; background-size:cover; border: 2px solid <?= $imagePlace === 'fg' ? 'red' : 'white' ?>; left: <?= $value['x'] ?? 0 ?>px; top:<?= $value['y'] ?? 0 ?>px;">
                                                </div>
                                            <?php } ?>
                                            <?php if (($value['type'] ?? '') == 'element') { ?>
                                                <div class="element-layer ui-draggable ui-draggable-handle" id="<?= Html::encode($value['id']) ?>" data-layer-type="element" data-element-type="<?= Html::encode($value['element_type'] ?? 'rectangle') ?>" style="position:absolute; left:<?= $value['x'] ?? 0 ?>px; top:<?= $value['y'] ?? 0 ?>px; width:<?= $value['width'] ?? 120 ?>px; height:<?= $value['height'] ?? 120 ?>px; transform:rotate(<?= $value['rotation'] ?? $value['rotate'] ?? 0 ?>deg); background-color:<?= Html::encode($value['color'] ?? '#60a5fa') ?>; opacity:<?= Html::encode($value['opacity'] ?? 1) ?>; border:<?= Html::encode($value['border_width'] ?? 2) ?>px solid <?= Html::encode($value['border_color'] ?? '#1d4ed8') ?>;"><?= in_array(($value['element_type'] ?? ''), ['svg', 'png'], true) ? Html::encode($value['element_type'] === 'svg' ? 'SVG' : 'PNG') : '' ?></div>
                                            <?php } ?>
                                    <?php }
                                    } ?>
                                </div>
                            <?php } ?>
                            </div>
                        </div>
                    </div>
	                    <div class="col-md-3 quick-access-panel">
                            <div class="selected-layer-info" id="selectedLayerInfo">
                                <strong>No layer selected</strong>
                                <span>Select a text or image layer from the left list or canvas.</span>
                            </div>
                            <button type="button" class="delete-layer-btn" id="deleteLayerBtn" disabled>Delete Selected Layer</button>
                        <div class="quick-tool-section quick-tool-text">
                            <h1 title="Text tools">Text</h1>
                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="item">Text</label>
	                                    <input type="text" name="item" value="<?= Html::encode($selectedText['text']) ?>" class="item slt" id="item" placeholder="Enter item name">
                                </div>
                                <div class="col-md-6 bt tool-action">
                                    <button type="button" class="btn btn-warning" id="add" title="Add text">Add Item</button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="textalign">Align</label>
                                    <select id="textalign" name="textalign" class="slt" onchange="changeNoteTextalign(this.value)">
	                                        <option value="" <?= empty($selectedText['textalign']) ? 'selected' : '' ?>>Select Text Align</option>
	                                        <option value="center" <?= $selectedText['textalign'] === 'center' ? 'selected' : '' ?>>center</option>
	                                        <option value="start" <?= $selectedText['textalign'] === 'start' ? 'selected' : '' ?>>start</option>
	                                        <option value="end" <?= $selectedText['textalign'] === 'end' ? 'selected' : '' ?>>end</option>
                                    </select>
                                </div>
                                <div class="col-md-6 tool-field">
                                    <label for="fontItalicToggle">Italic</label>
                                    <select id="fontstyle" name="fontstyle" class="slt" onchange="changeNoteFontstyle(this.value)">
	                                        <option value="" <?= empty($selectedText['fontstyle']) ? 'selected' : '' ?>>Select Font Style</option>
	                                        <option value="italic" <?= $selectedText['fontstyle'] === 'italic' ? 'selected' : '' ?>>italic</option>
	                                        <option value="normal" <?= $selectedText['fontstyle'] === 'normal' ? 'selected' : '' ?>>normal</option>
                                    </select>
                                    <button type="button" class="format-toggle" id="fontItalicToggle" title="Italic">I</button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <!-- <select id="fontcolor" name="fontcolor" class="slt" onchange="changeNoteColor(this.value)">
                                    <option value="" selected>Select Font Color</option>
                                    <option value="white">white</option>
                                    <option value="black">black</option>
                                    <option value="red">red</option>
                                </select>-->
                                    <label for="fontcolor" title="Text color">Text color</label>
                                    <input type="color" id="fontcolor" name="fontcolor" value="<?= Html::encode($selectedText['color']) ?>" class="font-color-picker" onchange="changeNoteColor(this.value)">
                                </div>
                                <div class="col-md-6">
                                    <label for="fontBoldToggle">Bold</label>
                                    <select id="fontweight" name="fontweight" class="slt" onchange="changeNoteFontweight(this.value)">
	                                        <option value="" <?= empty($selectedText['fontweight']) ? 'selected' : '' ?>>Select font weight</option>
	                                        <option value="bold" <?= $selectedText['fontweight'] === 'bold' ? 'selected' : '' ?>>bold</option>
	                                        <option value="normal" <?= $selectedText['fontweight'] === 'normal' ? 'selected' : '' ?>>normal</option>
                                    </select>
                                    <button type="button" class="format-toggle" id="fontBoldToggle" title="Bold">B</button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="posX">X position</label>
	                                    <input type="text" name="posX[]" id="posX" value="<?= Html::encode($selectedText['x']) ?>"  class="slt" placeholder="X position" />
                                </div>
                                <div class="col-md-6">
                                    <label for="posY">Y position</label>
	                                    <input type="text" name="posY[]" id="posY" value="<?= Html::encode($selectedText['y']) ?>" class="slt" placeholder="Y position" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="fontsize">Font size</label>
	                                    <input type="number" id="fontsize" value="<?= Html::encode($selectedText['fontsize']) ?>" class="slt" placeholder="select Font Size" min="8" max="200" onchange="changeNoteFontsize(this.value)">
                                </div>
                                <div class="col-md-6 font-size-tools tool-action">
                                    <label>Size step</label>
                                    <button type="button" class="btn btn-default font-size-step" id="increaseFontSize" title="Increase font size">A▲</button>
                                    <button type="button" class="btn btn-default font-size-step" id="decreaseFontSize" title="Decrease font size">A▼</button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 tool-field">
                                    <label for="fontfamily">Font family</label>
                                    <select id="fontfamily" name="fontfamily" class="slt" onchange="changeNoteFontfamily(this.value)">
	                                        <option value="" <?= empty($selectedText['fontfamily']) ? 'selected' : '' ?>>Select font family</option>
	                                        <option value="cursive" <?= $selectedText['fontfamily'] === 'cursive' ? 'selected' : '' ?>>cursive</option>
	                                        <option value="emoji" <?= $selectedText['fontfamily'] === 'emoji' ? 'selected' : '' ?>>emoji</option>
	                                        <option value="fantasy" <?= $selectedText['fontfamily'] === 'fantasy' ? 'selected' : '' ?>>fantasy</option>
	                                        <option value="inherit" <?= $selectedText['fontfamily'] === 'inherit' ? 'selected' : '' ?>>inherit</option>
	                                        <option value="initial" <?= $selectedText['fontfamily'] === 'initial' ? 'selected' : '' ?>>initial</option>
	                                        <option value="math" <?= $selectedText['fontfamily'] === 'math' ? 'selected' : '' ?>>math</option>
	                                        <option value="monospace" <?= $selectedText['fontfamily'] === 'monospace' ? 'selected' : '' ?>>monospace</option>
	                                        <option value="none" <?= $selectedText['fontfamily'] === 'none' ? 'selected' : '' ?>>none</option>
	                                        <option value="system-ui" <?= $selectedText['fontfamily'] === 'system-ui' ? 'selected' : '' ?>>system-ui</option>
	                                        <option value="Arial, sans-serif" <?= $selectedText['fontfamily'] === 'Arial, sans-serif' ? 'selected' : '' ?>>Arial</option>
	                                        <option value="Verdana, sans-serif" <?= $selectedText['fontfamily'] === 'Verdana, sans-serif' ? 'selected' : '' ?>>Verdana</option>
	                                        <option value="Tahoma, sans-serif" <?= $selectedText['fontfamily'] === 'Tahoma, sans-serif' ? 'selected' : '' ?>>Tahoma</option>
	                                        <option value="'Trebuchet MS', sans-serif" <?= $selectedText['fontfamily'] === "'Trebuchet MS', sans-serif" ? 'selected' : '' ?>>Trebuchet MS</option>
	                                        <option value="'Lucida Sans', sans-serif" <?= $selectedText['fontfamily'] === "'Lucida Sans', sans-serif" ? 'selected' : '' ?>>Lucida Sans</option>
	                                        <option value="Georgia, serif" <?= $selectedText['fontfamily'] === 'Georgia, serif' ? 'selected' : '' ?>>Georgia</option>
	                                        <option value="'Times New Roman', serif" <?= $selectedText['fontfamily'] === "'Times New Roman', serif" ? 'selected' : '' ?>>Times New Roman</option>
	                                        <option value="Garamond, serif" <?= $selectedText['fontfamily'] === 'Garamond, serif' ? 'selected' : '' ?>>Garamond</option>
	                                        <option value="'Courier New', monospace" <?= $selectedText['fontfamily'] === "'Courier New', monospace" ? 'selected' : '' ?>>Courier New</option>
	                                        <option value="Impact, fantasy" <?= $selectedText['fontfamily'] === 'Impact, fantasy' ? 'selected' : '' ?>>Impact</option>
	                                        <option value="'Comic Sans MS', cursive" <?= $selectedText['fontfamily'] === "'Comic Sans MS', cursive" ? 'selected' : '' ?>>Comic Sans MS</option>
	                                        <option value="'Brush Script MT', cursive" <?= $selectedText['fontfamily'] === "'Brush Script MT', cursive" ? 'selected' : '' ?>>Brush Script MT</option>
	                                        <option value="'Dancing Script', cursive" <?= $selectedText['fontfamily'] === "'Dancing Script', cursive" ? 'selected' : '' ?>>Dancing Script</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="textwidth">Text width</label>
	                                    <input type="text" name="textwidth" value="<?= Html::encode($selectedText['width']) ?>" id="textwidth" class="slt" placeholder="Text Width" />
                                </div>
                                <div class="col-md-6">
                                    <label for="textheight">Text height</label>
	                                    <input type="text" name="textheight" id="textheight" value="<?= Html::encode($selectedText['height']) ?>" class="slt" placeholder="Text Height" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="textrotate">Rotation</label>
	                                    <input type="number" name="textrotate" id="textrotate" value="<?= Html::encode($selectedText['rotate'] ?? 0) ?>" class="slt" placeholder="Text Rotate Degree" min="-360" max="360" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <input type="hidden" name="textid" class="textid" id="textid">
                                    <input type="hidden" name="merged_item_name" id="merged_item_name">
                                </div>
                                <div class="col-md-6">
                                    <input type="hidden" name="itemary[]">
                                </div>
                                <div class="col-md-6">
	                                    <input type="hidden" name="textwidth_hidden" id="textwidth_hidden">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
	                                    <button type="button" class="btn btn-primary" id="saveTextBtn" onclick="get_items()" title="Apply text">Update Text</button>
                                </div>
                            </div>


                        </div>


                        <div class="quick-tool-section quick-tool-image">
                            <h1 title="Image tools">Image</h1>
                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="imagePlace">Layer place</label>
                                    <select id="imagePlace" name="imagePlace" class="slt">
                                        <option value="" >Select Image place</option>
                                        <option value="bg">BG</option>
                                        <option value="fg">FG</option>
                                    </select>
                                </div>
                                <div class="col-md-6 bt tool-action">
                                    <button type="button" class="btn btn-warning" id="addImg" title="Add image">Add Image</button>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="posLeft">X position</label>
                                    <input type="text" name="posLeft[]" id="posLeft" class="slt" placeholder="X position" />
                                </div>
                                <div class="col-md-6 tool-field">
                                    <label for="posTop">Y position</label>
                                    <input type="text" name="posTop[]" id="posTop" class="slt" placeholder="Y position" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="height">Height</label>
                                    <input type="text" name="height[]" id="height" class="slt" placeholder="height" />
                                </div>
                                <div class="col-md-6 tool-field">
                                    <label for="width">Width</label>
                                    <input type="text" name="width[]" id="width" class="slt" placeholder="width" />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="rotate">Rotation</label>
                                    <input type="number" id="rotate" class="slt" placeholder="Select Rotate Degree" max="360" onchange="rotateOverlay(this.value)">
                                </div>
                                <div class="col-md-6 tool-field">
                                    <label for="imageScale">Scale %</label>
                                    <input type="number" id="imageScale" class="slt" placeholder="Scale %" min="1" value="100">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 tool-field">
                                    <label for="imageFilter">Filter</label>
                                    <select id="imageFilter" class="slt" title="Image filter">
                                        <option value="normal">Normal filter</option>
                                        <option value="grayscale">Black & White</option>
                                        <option value="sepia">Sepia</option>
                                        <option value="warm">Warm</option>
                                        <option value="cool">Cool</option>
                                        <option value="vintage">Vintage</option>
                                        <option value="bright">Bright</option>
                                        <option value="contrast">High Contrast</option>
                                        <option value="soft">Soft</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row image-outline-row" style="display:none;">
                                <div class="col-md-6 tool-field">
                                    <label for="imageOutline">Outline px</label>
                                    <input type="number" id="imageOutline" class="slt" placeholder="Outline px" min="0" max="80" value="0">
                                </div>
                                <div class="col-md-6 tool-field">
                                    <label for="imageOutlineColor">Outline color</label>
                                    <input type="color" id="imageOutlineColor" class="slt" value="#ffffff" title="Outline colour">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="imageRelativeTo">Align below</label>
                                    <select id="imageRelativeTo" class="slt">
                                        <option value="">None (Static)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 tool-field">
                                    <label for="imageOffsetY">Gap (Y Offset)</label>
                                    <input type="number" id="imageOffsetY" class="slt" placeholder="Offset Y px" value="15">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 bt tool-action">
	                                    <button type="button" class="btn btn-primary" id="saveImg" onclick="get_img()" title="Apply image">Update Image</button>
                                </div>
                                <div class="col-md-6">
                                    <input type="hidden" name="imgid" class="imgid" id="imgid">
                                </div>
                            </div>
                        </div>

                        <div class="quick-tool-section quick-tool-upload-image">
                            <h1 title="Upload image tools">Upload Image</h1>
                            <div class="row">
                                <div class="col-md-12 tool-field">
                                    <label for="layerImageUpload">Upload file</label>
                                    <input type="file" id="layerImageUpload" class="slt" accept="image/*">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="reuse-upload-option tool-check"><input type="checkbox" id="saveLayerImageForReuse"> Save for reuse</label>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 bt">
                                    <button type="button" class="btn btn-warning" id="uploadLayerImageBtn">Upload & Add</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 tool-field">
                                    <label for="reusableLayerImageSelect">Reusable image</label>
                                    <select id="reusableLayerImageSelect" class="slt">
                                        <option value="">Reusable images</option>
                                    </select>
                                    <div class="reuse-image-picker" id="reuseImagePicker">
                                        <img src="" alt="" class="reuse-image-thumb" id="reusableLayerSelectedThumb">
                                        <span class="reuse-image-thumb reuse-image-empty" id="reusableLayerEmptyThumb">No image</span>
                                        <span class="reuse-image-name" id="reusableLayerSelectedName">Reusable images</span>
                                        <button type="button" class="btn btn-default" id="openReusableLayerGalleryBtn">See More</button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 bt">
                                    <button type="button" class="btn btn-default" id="addReusableLayerImageBtn">Add</button>
                                </div>
                            </div>
                        </div>

                        <div class="quick-tool-section quick-tool-elements">
                            <h1 title="Element tools">Elements</h1>
                            <div class="row">
                                <div class="col-md-6 bt"><button type="button" class="btn btn-warning add-element" data-element-type="rectangle" title="Add shape">Add Shape</button></div>
                                <div class="col-md-6 bt"><button type="button" class="btn btn-warning add-element" data-element-type="line" title="Add line">Add Line</button></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 bt"><button type="button" class="btn btn-warning add-element" data-element-type="circle" title="Add circle">Add Circle</button></div>
                                <div class="col-md-6 bt"><button type="button" class="btn btn-warning add-element" data-element-type="rectangle" title="Add rectangle">Add Rectangle</button></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 bt"><button type="button" class="btn btn-warning add-element" data-element-type="star" title="Add star">Add Star</button></div>
                                <div class="col-md-6 bt"><button type="button" class="btn btn-warning add-element" data-element-type="svg" title="Add SVG icon">Add Icon / SVG</button></div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 bt"><button type="button" class="btn btn-warning add-element" data-element-type="png" title="Add PNG sticker">Add Sticker / PNG</button></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 tool-field"><label for="elementX">X position</label><input type="text" id="elementX" class="slt" placeholder="X position"></div>
                                <div class="col-md-6 tool-field"><label for="elementY">Y position</label><input type="text" id="elementY" class="slt" placeholder="Y position"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 tool-field"><label for="elementWidth">Width</label><input type="text" id="elementWidth" class="slt" placeholder="Width"></div>
                                <div class="col-md-6 tool-field"><label for="elementHeight">Height</label><input type="text" id="elementHeight" class="slt" placeholder="Height"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 tool-field"><label for="elementRotation">Rotation</label><input type="number" id="elementRotation" class="slt" placeholder="Rotation" min="-360" max="360"></div>
                                <div class="col-md-6 tool-field"><label for="elementScale">Scale %</label><input type="number" id="elementScale" class="slt" placeholder="Scale %" min="1" value="100"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 tool-field"><label for="elementColor" title="Fill color">Fill color</label><input type="color" id="elementColor" class="font-color-picker" value="#60a5fa"></div>
                                <div class="col-md-6 tool-field"><label for="elementOpacity" title="Opacity">Opacity</label><input type="number" id="elementOpacity" class="slt" min="0" max="1" step="0.05" value="1"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 tool-field"><label for="elementBorderColor" title="Border color">Border color</label><input type="color" id="elementBorderColor" class="font-color-picker" value="#1d4ed8"></div>
                                <div class="col-md-6 tool-field"><label for="elementBorderWidth">Border width</label><input type="number" id="elementBorderWidth" class="slt" placeholder="Border width" min="0" max="50" value="2"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="elementRelativeTo">Align below</label>
                                    <select id="elementRelativeTo" class="slt">
                                        <option value="">None (Static)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 tool-field">
                                    <label for="elementOffsetY">Gap (Y Offset)</label>
                                    <input type="number" id="elementOffsetY" class="slt" placeholder="Offset Y px" value="15">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 tool-field">
                                    <label for="elementPadding">Box Padding (px)</label>
                                    <input type="number" id="elementPadding" class="slt" placeholder="Auto (Equal margins)" min="0" max="100">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><button type="button" class="btn btn-primary" id="saveElementBtn" title="Apply element">Update Element</button></div>
                                <div class="col-md-6"><input type="hidden" id="elementId"></div>
                            </div>
                        </div>

                        <div class="appenddiv"></div>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-success']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<div class="product-preview-popup" id="productPreviewPopup" aria-hidden="true">
    <div class="product-preview-dialog" role="dialog" aria-modal="true" aria-label="Product image preview">
        <button type="button" class="product-preview-close" id="productPreviewClose" aria-label="Close preview">&times;</button>
        <div class="preview-image-container">
            <img src="<?= Yii::getAlias('@storageUrl') ?>/images/<?= Html::encode($previewImageData) ?>" alt="Product preview large" id="productPreviewLargeImg">
            <div class="preview-grid-overlay" id="previewGridOverlay"></div>
            <div class="preview-ruler preview-ruler-top" id="previewRulerTop"></div>
            <div class="preview-ruler preview-ruler-left" id="previewRulerLeft"></div>
        </div>
    </div>
</div>

<div class="reuse-gallery-popup" id="reuseGalleryPopup" aria-hidden="true">
    <div class="reuse-gallery-dialog" role="dialog" aria-modal="true" aria-label="Reusable image gallery">
        <button type="button" class="reuse-gallery-close" id="reuseGalleryClose" aria-label="Close reusable image gallery">&times;</button>
        <h2>Reusable Images</h2>
        <div class="reuse-gallery-grid" id="reuseGalleryGrid">
            <div class="reuse-gallery-empty">No reusable image found</div>
        </div>
    </div>
</div>

<div class="image-convert-popup" id="imageConvertPopup" aria-hidden="true">
    <div class="image-convert-dialog" role="dialog" aria-modal="true" aria-label="Convert image to WebP">
        <button type="button" class="image-convert-close" id="imageConvertClose" aria-label="Close image converter">&times;</button>
        <h2>Convert to WebP</h2>
        <div class="image-convert-body">
            <div class="image-convert-preview">
                <canvas id="imageConvertCanvas"></canvas>
            </div>
            <div class="image-convert-tools">
                <div class="image-convert-meta" id="imageConvertMeta">Select JPG or PNG image</div>
                <label>Width
                    <input type="number" id="imageConvertWidth" min="1" step="1">
                </label>
                <label>Height
                    <input type="number" id="imageConvertHeight" min="1" step="1">
                </label>
                <label>Quality <span id="imageConvertQualityText">86%</span>
                    <input type="range" id="imageConvertQuality" min="20" max="100" value="86">
                </label>
                <div class="image-convert-meta" id="imageConvertOutputMeta">Output: WebP</div>
                <div class="image-convert-actions">
                    <button type="button" class="btn btn-default" id="imageConvertCancel">Cancel</button>
                    <button type="button" class="btn btn-primary" id="imageConvertUpload">Convert & Upload</button>
                </div>
            </div>
        </div>
    </div>
</div>




<script>
    function get_images(language_id) {
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        var template_id = $('#product-template_id').val();
        var path = '<?= Yii::getAlias('@storageUrl') ?>/images/'
        $.ajax({
            url: "<?= Url::toRoute(['product/images']) ?>",
            method: "POST",
            data: {
                language_id: language_id,
                template_id: template_id,
                '_csrf-backend': csrfToken
            },
            success: function(result) {
                if (result != '') {

                    var imgtype = JSON.parse(result);
                    console.log(imgtype.image_type);

                    var img = path + imgtype.img;
                    console.log(img);
                    if (imgtype.image_type == 'square') {
                        console.log('true');
                        $('.xyz .template-background-layer').css("background-image", "url(\"" + path + imgtype.img + "\")").attr('data-background-url', path + imgtype.img);
                        $('.xyz').css("display", "block");
                        $('.xyz1').css("display", "none");
                        applyCanvasScale($('#canvasScale').val());

                    } else {
                        console.log('false');
                        $('.xyz1 .template-background-layer').css("background-image", "url(\"" + path + imgtype.img + "\")").attr('data-background-url', path + imgtype.img);
                        $('.xyz1').css("display", "block");
                        $('.xyz').css("display", "none");
                        applyCanvasScale($('#canvasScale').val());
                    }
                }
            }
        });
    }
</script>






<script>
	    var existingList = <?= Json::htmlEncode((string) $model->item_name) ?>;
	    // var updateList = JSON.parse(existingList);
	    var rawLayerList = existingList ? JSON.parse(existingList) : [];
	    var updateList = rawLayerList.filter(function(layer) {
            return layer.type !== 'background';
        });
	    var appendedList = [];
        var templateBackgroundLayer = {
            id: 'template-background',
            type: 'background',
            text: 'Template Background',
            x: 0,
            y: 0,
            width: '100%',
            height: '100%',
            rotate: 0
        };
        rawLayerList.forEach(function(layer) {
            if (layer.type === 'background') {
                templateBackgroundLayer.z = layer.z;
                templateBackgroundLayer.z_index = layer.z_index;
                templateBackgroundLayer.layer_rank_version = layer.layer_rank_version;
            }
        });
        var selectedLayerId = '';
        var selectedLayerType = '';
        var layerOrder = buildInitialLayerOrder();
        var undoStack = [];
        var isRestoringUndo = false;
        var pendingFieldUndoSnapshot = false;
        var canvasScale = 1;
        var canvasRulerOffsetX = 32;
        var canvasRulerOffsetY = 24;

        function cloneData(value) {
            return JSON.parse(JSON.stringify(value));
        }

        function captureEditorSnapshot() {
            return {
                updateList: cloneData(updateList),
                layerOrder: cloneData(layerOrder),
                selectedLayerId: selectedLayerId,
                selectedLayerType: selectedLayerType,
                textid: $('#textid').val(),
                imgid: $('#imgid').val(),
                elementId: $('#elementId').val()
            };
        }

        function recordUndoSnapshot() {
            if (isRestoringUndo) {
                return;
            }

            var snapshot = captureEditorSnapshot();
            var serialized = JSON.stringify(snapshot);
            var last = undoStack.length ? JSON.stringify(undoStack[undoStack.length - 1]) : '';

            if (serialized === last) {
                return;
            }

            undoStack.push(snapshot);
            if (undoStack.length > 50) {
                undoStack.shift();
            }
        }

        function restoreEditorSnapshot(snapshot) {
            if (!snapshot) {
                return;
            }

            isRestoringUndo = true;
            updateList = cloneData(snapshot.updateList || []);
            layerOrder = cloneData(snapshot.layerOrder || []);
            selectedLayerId = snapshot.selectedLayerId || '';
            selectedLayerType = snapshot.selectedLayerType || '';
            $('#textid').val(snapshot.textid || '');
            $('#imgid').val(snapshot.imgid || '');
            $('#elementId').val(snapshot.elementId || '');
            rebuildCanvasLayersFromData();
            syncLayerArrays();
            renderLayerList();

            if (selectedLayerType === 'text' && selectedLayerId) {
                selectTextLayer(selectedLayerId);
            } else if (selectedLayerType === 'image' && selectedLayerId) {
                selectImageLayer(selectedLayerId);
            } else if (selectedLayerType === 'element' && selectedLayerId) {
                selectElementLayer(selectedLayerId);
            } else if (selectedLayerType === 'background') {
                selectBackgroundLayer();
            } else {
                clearSelectedLayer();
            }

            releaseTemporaryLayerPreview();
            isRestoringUndo = false;
        }

        function undoLastEditorStep() {
            if (!undoStack.length) {
                return false;
            }

            restoreEditorSnapshot(undoStack.pop());
            return true;
        }

        function getCanvasScale() {
            return canvasScale || 1;
        }

        function getActiveCanvas() {
            return $('.canvas-scale-target:visible').first();
        }

        function applyCanvasScale(scalePercent) {
            canvasScale = (parseFloat(scalePercent) || 100) / 100;
            var $canvas = getActiveCanvas();

            if (!$canvas.length) {
                return;
            }

            var baseWidth = $canvas.outerWidth();
            var baseHeight = $canvas.outerHeight();

            $canvas.css({
                zoom: canvasScale,
                transform: 'none'
            });
            $('#canvasScaleShell').css({
                width: Math.ceil(baseWidth * canvasScale) + canvasRulerOffsetX + 'px',
                height: Math.ceil(baseHeight * canvasScale) + canvasRulerOffsetY + 'px'
            });
            $('#canvasScaleValue').text(Math.round(canvasScale * 100) + '%');
            updateCanvasOverlays(baseWidth, baseHeight);
        }

        function updateCanvasOverlays(baseWidth, baseHeight) {
            var scaledWidth = Math.ceil(baseWidth * canvasScale);
            var scaledHeight = Math.ceil(baseHeight * canvasScale);
            var gridSize = Math.max(6, Math.round(50 * canvasScale));

            $('#canvasGridOverlay')
                .toggle($('#showCanvasGrid').is(':checked'))
                .css({
                    left: canvasRulerOffsetX + 'px',
                    top: canvasRulerOffsetY + 'px',
                    width: scaledWidth + 'px',
                    height: scaledHeight + 'px',
                    'background-size': (gridSize * 2) + 'px ' + (gridSize * 2) + 'px, ' + (gridSize * 2) + 'px ' + (gridSize * 2) + 'px, ' + gridSize + 'px ' + gridSize + 'px, ' + gridSize + 'px ' + gridSize + 'px'
                });

            $('#canvasGuideVertical').css({
                top: canvasRulerOffsetY + 'px',
                height: scaledHeight + 'px'
            });
            $('#canvasGuideHorizontal').css({
                left: canvasRulerOffsetX + 'px',
                width: scaledWidth + 'px'
            });
            drawCanvasRulers(baseWidth, baseHeight);

            if ($('#showCanvasGuides').is(':checked')) {
                var selected = getSelectedLayerElement();
                if (selected.$layer && selected.$layer.length) {
                    updateGuideLines(selected.$layer);
                } else {
                    showCanvasCenterGuides(scaledWidth, scaledHeight);
                }
            } else {
                hideGuideLines();
            }
        }

        function showCanvasCenterGuides(scaledWidth, scaledHeight) {
            $('#canvasGuideVertical').css({
                left: Math.round(canvasRulerOffsetX + (scaledWidth / 2)) + 'px',
                top: canvasRulerOffsetY + 'px',
                height: scaledHeight + 'px'
            }).show();
            $('#canvasGuideHorizontal').css({
                left: canvasRulerOffsetX + 'px',
                top: Math.round(canvasRulerOffsetY + (scaledHeight / 2)) + 'px',
                width: scaledWidth + 'px'
            }).show();
        }

        function drawCanvasRulers(baseWidth, baseHeight) {
            var showRulers = $('#showCanvasRulers').is(':checked');
            var scaledWidth = Math.ceil(baseWidth * canvasScale);
            var scaledHeight = Math.ceil(baseHeight * canvasScale);
            var tickEvery = 50;
            var majorEvery = 100;
            var topHtml = '';
            var leftHtml = '';

            for (var x = 0; x <= baseWidth; x += tickEvery) {
                var left = Math.round(x * canvasScale);
                var isMajorX = x % majorEvery === 0;
                topHtml += '<span class="ruler-tick" style="left:' + left + 'px;height:' + (isMajorX ? 14 : 8) + 'px"></span>';
                if (isMajorX) {
                    topHtml += '<span class="ruler-label" style="left:' + (left + 3) + 'px;top:2px">' + x + '</span>';
                }
            }

            for (var y = 0; y <= baseHeight; y += tickEvery) {
                var top = Math.round(y * canvasScale);
                var isMajorY = y % majorEvery === 0;
                leftHtml += '<span class="ruler-tick" style="top:' + top + 'px;width:' + (isMajorY ? 18 : 10) + 'px"></span>';
                if (isMajorY) {
                    leftHtml += '<span class="ruler-label" style="left:2px;top:' + (top + 2) + 'px">' + y + '</span>';
                }
            }

            $('#canvasRulerTop')
                .toggle(showRulers)
                .css({
                    left: canvasRulerOffsetX + 'px',
                    width: scaledWidth + 'px'
                })
                .html(topHtml);
            $('#canvasRulerLeft')
                .toggle(showRulers)
                .css({
                    top: canvasRulerOffsetY + 'px',
                    height: scaledHeight + 'px'
                })
                .html(leftHtml);
        }

        function updateGuideLines($layer) {
            if (!$('#showCanvasGuides').is(':checked') || !$layer || !$layer.length) {
                hideGuideLines();
                return;
            }

            var centerX = ((parseFloat($layer.css('left')) || 0) + ($layer.outerWidth() / 2)) * canvasScale;
            var centerY = ((parseFloat($layer.css('top')) || 0) + ($layer.outerHeight() / 2)) * canvasScale;

            $('#canvasGuideVertical').css({ left: Math.round(canvasRulerOffsetX + centerX) + 'px' }).show();
            $('#canvasGuideHorizontal').css({ top: Math.round(canvasRulerOffsetY + centerY) + 'px' }).show();
        }

        function hideGuideLines() {
            $('#canvasGuideVertical, #canvasGuideHorizontal').hide();
        }

        function getSelectedLayerElement() {
            if ($('#textid').val()) {
                return {
                    type: 'text',
                    $layer: getCanvasLayer($('#textid').val(), 'text')
                };
            }

            if ($('#imgid').val()) {
                return {
                    type: 'image',
                    $layer: getCanvasLayer($('#imgid').val(), 'image')
                };
            }

            if ($('#elementId').val()) {
                return {
                    type: 'element',
                    $layer: getCanvasLayer($('#elementId').val(), 'element')
                };
            }

            return {
                type: '',
                $layer: $()
            };
        }

        function nudgeSelectedLayer(deltaX, deltaY) {
            var selected = getSelectedLayerElement();

            if (!selected.$layer.length) {
                return false;
            }

            recordUndoSnapshot();
            var $layer = selected.$layer;
            var $parent = $layer.parent();
            var nextLeft = (parseFloat($layer.css('left')) || 0) + deltaX;
            var nextTop = (parseFloat($layer.css('top')) || 0) + deltaY;
            var maxLeft = Math.max(0, $parent.outerWidth() - $layer.outerWidth());
            var maxTop = Math.max(0, $parent.outerHeight() - $layer.outerHeight());

            nextLeft = Math.max(0, Math.min(nextLeft, maxLeft));
            nextTop = Math.max(0, Math.min(nextTop, maxTop));

            $layer.css({
                left: nextLeft + 'px',
                top: nextTop + 'px'
            });

            var ui = {
                position: {
                    left: nextLeft,
                    top: nextTop
                }
            };

            if (selected.type === 'text') {
                syncTextLayerPosition($layer, ui);
            } else if (selected.type === 'image') {
                syncImageLayerPosition($layer, ui);
            } else {
                syncElementLayerPosition($layer, ui);
            }

            syncLayerArrays();
            return true;
        }

        function rememberScaledDragStart($element, event, ui) {
            $element.data('scaled-drag-start', {
                left: ui.position.left,
                top: ui.position.top,
                pageX: event.pageX,
                pageY: event.pageY
            });
        }

        function applyScaledDragPosition($element, event, ui) {
            return ui;
        }

        function enableScaledLayerDrag($elements, type) {
            $elements.each(function() {
                var $element = $(this);

                if ($element.data('ui-draggable')) {
                    $element.draggable('destroy');
                }

                if ($element.data('scaled-layer-drag-ready')) {
                    return;
                }

                $element.data('scaled-layer-drag-ready', true);
                $element.on('mousedown.scaledLayerDrag', function(event) {
                    if ($(event.target).hasClass('ui-resizable-handle')) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();

                    var $current = $(this);
                    var startLeft = parseFloat($current.css('left')) || 0;
                    var startTop = parseFloat($current.css('top')) || 0;
                    var startX = event.pageX;
                    var startY = event.pageY;
                    var $parent = $current.parent();

                    recordUndoSnapshot();
                    if (type === 'text') {
                        selectTextLayer($current.attr('id'));
                    } else if (type === 'image') {
                        selectImageLayer($current.attr('id'));
                    } else {
                        selectElementLayer($current.attr('id'));
                    }

                    $(document)
                        .off('.scaledLayerDragMove')
                        .on('mousemove.scaledLayerDragMove', function(moveEvent) {
                            var scale = getCanvasScale();
                            var nextLeft = startLeft + ((moveEvent.pageX - startX) / scale);
                            var nextTop = startTop + ((moveEvent.pageY - startY) / scale);
                            var maxLeft = Math.max(0, $parent.outerWidth() - $current.outerWidth());
                            var maxTop = Math.max(0, $parent.outerHeight() - $current.outerHeight());

                            nextLeft = Math.max(0, Math.min(nextLeft, maxLeft));
                            nextTop = Math.max(0, Math.min(nextTop, maxTop));

                            $current.css({
                                left: nextLeft + 'px',
                                top: nextTop + 'px'
                            });

                            var ui = {
                                position: {
                                    left: nextLeft,
                                    top: nextTop
                                }
                            };

                            if (type === 'text') {
                                syncTextLayerPosition($current, ui);
                            } else if (type === 'image') {
                                syncImageLayerPosition($current, ui);
                            } else {
                                syncElementLayerPosition($current, ui);
                            }
                        })
                        .on('mouseup.scaledLayerDragMove', function(upEvent) {
                            $(document).off('.scaledLayerDragMove');
                            var finalUi = {
                                position: {
                                    left: parseFloat($current.css('left')) || 0,
                                    top: parseFloat($current.css('top')) || 0
                                }
                            };

                            if (type === 'text') {
                                syncTextLayerPosition($current, finalUi);
                            } else if (type === 'image') {
                                syncImageLayerPosition($current, finalUi);
                            } else {
                                syncElementLayerPosition($current, finalUi);
                            }
                        });
                });
            });
        }

        function findLayerData(id, type) {
            if (type === 'background') {
                return templateBackgroundLayer;
            }

            var numericId = parseInt(id, 10);
            return updateList.find(function(layer) {
                return parseInt(layer.id, 10) === numericId && (!type || layer.type === type);
            }) || {};
        }

        function getCanvasLayer(id, type) {
            return $('[data-layer-type="' + type + '"]').filter(function() {
                return String(this.id) === String(id);
            }).first();
        }

        function getLayerKey(id, type) {
            return String(type || '') + ':' + String(id || '');
        }

        function isFiniteLayerNumber(value) {
            return value !== null && value !== '' && isFinite(parseFloat(value));
        }

        function getSavedLayerRank(layer) {
            if (parseInt(layer.layer_rank_version || 0, 10) !== 2) {
                return null;
            }

            if (isFiniteLayerNumber(layer.z_index)) {
                return parseFloat(layer.z_index);
            }

            if (isFiniteLayerNumber(layer.z)) {
                return parseFloat(layer.z);
            }

            return null;
        }

        function getDefaultLayerRank(layer, fallbackIndex) {
            return (getLayerBand(layer) * 1000) + fallbackIndex;
        }

        function buildInitialLayerOrder() {
            var layers = [templateBackgroundLayer].concat(updateList);
            var hasSavedRank = layers.some(function(layer) {
                return getSavedLayerRank(layer) !== null;
            });

            return layers
                .map(function(layer, index) {
                    var savedRank = getSavedLayerRank(layer);
                    return {
                        key: getLayerKey(layer.id, layer.type),
                        rank: hasSavedRank && savedRank !== null ? savedRank : getDefaultLayerRank(layer, index),
                        index: index
                    };
                })
                .sort(function(a, b) {
                    if (a.rank === b.rank) {
                        return a.index - b.index;
                    }
                    return a.rank - b.rank;
                })
                .map(function(item) {
                    return item.key;
                });
        }

        function getAllRenderableLayers() {
            var layers = [templateBackgroundLayer].concat(updateList);
            layers.forEach(function(layer) {
                var key = getLayerKey(layer.id, layer.type);
                if (layerOrder.indexOf(key) === -1) {
                    layerOrder.push(key);
                }
            });
            layerOrder = layerOrder.filter(function(key) {
                return layers.some(function(layer) {
                    return getLayerKey(layer.id, layer.type) === key;
                });
            });
            return layers;
        }

        function addLayerToOrder(idOrLayer, type) {
            var layer = typeof idOrLayer === 'object' ? idOrLayer : {
                id: idOrLayer,
                type: type
            };
            var key = getLayerKey(layer.id, layer.type);

            if (layerOrder.indexOf(key) !== -1) {
                return;
            }

            if (layer.type === 'image') {
                var place = String(layer.place || '').toLowerCase();
                var backgroundKey = getLayerKey(templateBackgroundLayer.id, templateBackgroundLayer.type);
                var backgroundIndex = layerOrder.indexOf(backgroundKey);

                if (place === 'bg' && backgroundIndex >= 0) {
                    layerOrder.splice(backgroundIndex, 0, key);
                    return;
                }

                if (place === 'fg' && backgroundIndex >= 0) {
                    layerOrder.splice(backgroundIndex + 1, 0, key);
                    return;
                }
            }

            layerOrder.push(key);
        }

        function getLayerBand(layer) {
            if (layer.type === 'image' && String(layer.place || '').toLowerCase() === 'bg') {
                return 0;
            }

            if (layer.type === 'background') {
                return 1;
            }

            if (layer.type === 'text') {
                return 3;
            }

            return 2;
        }

        function getLayerOrderIndex(layer) {
            var index = layerOrder.indexOf(getLayerKey(layer.id, layer.type));
            return Math.max(0, index);
        }

        function getLayerZ(layer) {
            getAllRenderableLayers();
            return 100 + getLayerOrderIndex(layer);
        }

        function getLayerLabel(layer) {
            if (layer.type === 'background') {
                return 'Background image';
            }

            if (layer.type === 'element') {
                return 'Element layer: ' + String(layer.element_type || 'element').replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });
            }

            if (layer.type === 'image') {
                if (layer.image_src) {
                    return 'Image layer: Upload';
                }
                return 'Image layer: ' + String(layer.place || 'image').toUpperCase();
            }

            return 'Text layer: ' + (layer.text || 'Text');
        }

        function buildTextLayerElement(layer) {
            return $('<div class="note ui-draggable ui-draggable-handle unique-class"/>')
                .attr('id', layer.id)
                .attr('data-layer-type', 'text')
                .text(layer.text || '')
                .css({
                    border: '1px solid yellow',
                    width: (layer.width || 1) + 'px',
                    height: (layer.height || 1) + 'px',
                    position: 'absolute',
                    left: (layer.x || 0) + 'px',
                    top: (layer.y || 0) + 'px',
                    'text-align': layer.textalign || '',
                    color: layer.color || '',
                    'font-style': layer.fontstyle || '',
                    'font-weight': layer.fontweight || '',
                    'font-size': (layer.fontsize || '') + 'px',
                    'font-family': layer.fontfamily || '',
                    transform: 'rotate(' + (layer.rotate || 0) + 'deg)'
                });
        }

        function buildImageLayerElement(layer) {
            var place = String(layer.place || 'fg').toLowerCase();
            var imageUrl = getLayerImageUrl(layer);
            return $('<div class="overlay ui-draggable ui-draggable-handle"/>')
                .attr('id', layer.id)
                .attr('data-layer-type', 'image')
                .attr('data-base-width', layer.baseWidth || layer.width || 100)
                .attr('data-base-height', layer.baseHeight || layer.height || 100)
                .css({
                    position: 'absolute',
                    width: (layer.width || 100) + 'px',
                    height: (layer.height || 100) + 'px',
                    transform: 'rotate(' + (layer.rotate || 0) + 'deg)',
                    'background-color': imageUrl ? 'transparent' : 'rgba(0, 0, 0, 0.5)',
                    'background-image': imageUrl ? 'url("' + imageUrl + '")' : '',
                    'background-position': 'center center',
                    'background-repeat': 'no-repeat',
                    'background-size': 'cover',
                    filter: getImageCssFilter(layer.image_filter || layer.filter || 'normal'),
                    border: '2px solid ' + (place === 'fg' ? 'red' : 'white'),
                    left: (layer.x || 0) + 'px',
                    top: (layer.y || 0) + 'px'
                });
        }

        function getImageCssFilter(filterName) {
            var filters = {
                normal: 'none',
                grayscale: 'grayscale(100%)',
                sepia: 'sepia(75%) contrast(105%)',
                warm: 'sepia(18%) saturate(130%) brightness(104%)',
                cool: 'saturate(115%) hue-rotate(178deg) brightness(102%)',
                vintage: 'sepia(45%) contrast(95%) brightness(98%) saturate(85%)',
                bright: 'brightness(115%) saturate(110%)',
                contrast: 'contrast(135%) saturate(108%)',
                soft: 'brightness(108%) contrast(88%) saturate(92%)'
            };

            return filters[filterName] || filters.normal;
        }

        function getImageFilterValue() {
            var allowed = ['normal', 'grayscale', 'sepia', 'warm', 'cool', 'vintage', 'bright', 'contrast', 'soft'];
            var value = $('#imageFilter').val() || 'normal';

            return allowed.indexOf(value) >= 0 ? value : 'normal';
        }

        function getLayerImageUrl(layer) {
            if (layer.image_src) {
                return '<?= Yii::getAlias('@storageUrl') ?>/images/' + layer.image_src;
            }

            if (layer.image_url) {
                return layer.image_url;
            }

            return '';
        }

        function buildElementLayerElement(layer) {
            return $('<div class="element-layer ui-draggable ui-draggable-handle"/>')
                .attr('id', layer.id)
                .attr('data-layer-type', 'element')
                .attr('data-element-type', layer.element_type || 'rectangle')
                .text(getElementDisplayText(layer.element_type))
                .css(getElementCss(layer));
        }

        function getElementDisplayText(elementType) {
            if (elementType === 'svg') {
                return 'SVG';
            }
            if (elementType === 'png') {
                return 'PNG';
            }
            return '';
        }

        function getElementCss(layer) {
            var elementType = layer.element_type || 'rectangle';
            var color = layer.color || '#60a5fa';
            var height = elementType === 'line' ? Math.max(1, parseFloat(layer.height || 4)) : (layer.height || 120);

            return {
                position: 'absolute',
                left: (layer.x || 0) + 'px',
                top: (layer.y || 0) + 'px',
                width: (layer.width || 120) + 'px',
                height: height + 'px',
                transform: 'rotate(' + (layer.rotation || layer.rotate || 0) + 'deg)',
                'background-color': color,
                color: color,
                opacity: layer.opacity || 1,
                border: (layer.border_width || 0) + 'px solid ' + (layer.border_color || '#1d4ed8')
            };
        }

        function applyLayerDataToElement(layer) {
            var $layer = getCanvasLayer(layer.id, layer.type);

            if (!$layer.length) {
                return;
            }

            if (layer.type === 'text') {
                $layer.text(layer.text || '').css({
                    left: (layer.x || 0) + 'px',
                    top: (layer.y || 0) + 'px',
                    width: (layer.width || 1) + 'px',
                    height: (layer.height || 1) + 'px',
                    color: layer.color || '',
                    'text-align': layer.textalign || '',
                    'font-style': layer.fontstyle || '',
                    'font-weight': layer.fontweight || '',
                    'font-size': (layer.fontsize || '') + 'px',
                    'font-family': layer.fontfamily || '',
                    transform: 'rotate(' + (layer.rotate || 0) + 'deg)'
                });
            } else if (layer.type === 'image') {
                var place = String(layer.place || 'fg').toLowerCase();
                var imageUrl = getLayerImageUrl(layer);
                $layer
                    .attr('data-base-width', layer.baseWidth || layer.width || 100)
                    .attr('data-base-height', layer.baseHeight || layer.height || 100)
                    .css({
                        left: (layer.x || 0) + 'px',
                        top: (layer.y || 0) + 'px',
                        width: (layer.width || 100) + 'px',
                        height: (layer.height || 100) + 'px',
                        transform: 'rotate(' + (layer.rotate || 0) + 'deg)',
                        'background-color': imageUrl ? 'transparent' : 'rgba(0, 0, 0, 0.5)',
                        'background-image': imageUrl ? 'url("' + imageUrl + '")' : '',
                        'background-position': 'center center',
                        'background-repeat': 'no-repeat',
                        'background-size': 'cover',
                        filter: getImageCssFilter(layer.image_filter || layer.filter || 'normal'),
                        border: '2px solid ' + (place === 'fg' ? 'red' : 'white')
                    });
            } else if (layer.type === 'element') {
                $layer
                    .attr('data-element-type', layer.element_type || 'rectangle')
                    .text(getElementDisplayText(layer.element_type))
                    .css(getElementCss(layer));
            }
        }

        function rebuildCanvasLayersFromData() {
            var $canvas = getActiveCanvas();
            var existingKeys = updateList.map(function(layer) {
                return getLayerKey(layer.id, layer.type);
            });

            if (!$canvas.length) {
                return;
            }

            $('.note, .overlay, .element-layer').each(function() {
                var key = getLayerKey(this.id, $(this).data('layer-type'));
                if (existingKeys.indexOf(key) === -1) {
                    $(this).remove();
                }
            });

            updateList.forEach(function(layer) {
                if (!getCanvasLayer(layer.id, layer.type).length) {
                    if (layer.type === 'text') {
                        $canvas.append(buildTextLayerElement(layer));
                    } else if (layer.type === 'image') {
                        $canvas.append(buildImageLayerElement(layer));
                    } else if (layer.type === 'element') {
                        $canvas.append(buildElementLayerElement(layer));
                    }
                }
                applyLayerDataToElement(layer);
            });

            enableLayerMovement();
            applyLayerStack();
        }

        function syncLayerArrays() {
            applyLayerStack();
            reorderUpdateListByLayerOrder();
            if (typeof updateImage !== 'undefined' && Array.isArray(updateImage)) {
                updateImage = updateList.slice();
            }
            $('#merged_item_name').val(JSON.stringify([templateBackgroundLayer].concat(updateList)));
        }

        function reorderUpdateListByLayerOrder() {
            updateList.sort(function(a, b) {
                return getLayerOrderIndex(a) - getLayerOrderIndex(b);
            });
        }

        function applyLayerStack() {
            getAllRenderableLayers().forEach(function(layer) {
                var $layer = getCanvasLayer(layer.id, layer.type);
                var zIndex = getLayerZ(layer);
                layer.z = zIndex;
                layer.z_index = zIndex;
                layer.layer_rank_version = 2;

                if (!$layer.length) {
                    return;
                }

                $layer.css('z-index', zIndex);

                if (layer.type === 'image') {
                    var place = String(layer.place || 'fg').toLowerCase();
                    $layer.css('border', '2px solid ' + (place === 'fg' ? 'red' : 'white'));
                }
            });
        }

        function populateTextLayersDropdowns() {
            var textLayers = updateList.filter(function(layer) {
                return layer.type === 'text';
            });
            var $imageSelect = $('#imageRelativeTo');
            var $elementSelect = $('#elementRelativeTo');
            var currentImgVal = $imageSelect.val();
            var currentElVal = $elementSelect.val();
            var optionsHtml = '<option value="">None (Static)</option>';
            textLayers.forEach(function(layer) {
                var label = (layer.text || 'Text') + ' (' + layer.id + ')';
                optionsHtml += '<option value="' + escapeHtml(layer.id) + '">' + escapeHtml(label) + '</option>';
            });
            $imageSelect.html(optionsHtml).val(currentImgVal);
            $elementSelect.html(optionsHtml).val(currentElVal);
        }

        function renderLayerList() {
            populateTextLayersDropdowns();
            var $list = $('#layerList');
            var selectedId = selectedLayerId || $('#textid').val() || $('#imgid').val();
            var selectedType = selectedLayerType || ($('#textid').val() ? 'text' : ($('#imgid').val() ? 'image' : ''));

            if (!$list.length) {
                return;
            }

            applyLayerStack();
            var ordered = getAllRenderableLayers().slice().sort(function(a, b) {
                return getLayerZ(b) - getLayerZ(a);
            });
            var html = ordered.map(function(layer) {
                var type = layer.type || '';
                var id = layer.id || '';
                var isSelected = String(id) === String(selectedId) && type === selectedType;
                var listIndex = layerOrder.indexOf(getLayerKey(id, type));
                var canMoveUp = listIndex < layerOrder.length - 1;
                var canMoveDown = listIndex > 0;
                var detail = '';

                if (type === 'background') {
                    detail =
                        '<span>X: 0, Y: 0</span>' +
                        '<span>Canvas background image</span>';
                } else if (type === 'element') {
                    detail =
                        '<span>X: ' + escapeHtml(layer.x) + ', Y: ' + escapeHtml(layer.y) + '</span>' +
                        '<span>' + escapeHtml(layer.width) + ' x ' + escapeHtml(layer.height) + ' px, Scale: ' + escapeHtml(layer.scale || 100) + '%, Rotate: ' + escapeHtml(layer.rotation || 0) + '</span>';
                } else if (type === 'text') {
                    detail =
                        '<span>X: ' + escapeHtml(layer.x) + ', Y: ' + escapeHtml(layer.y) + '</span>' +
                        '<span>Color: ' + escapeHtml(layer.color) + ', Size: ' + escapeHtml(layer.fontsize) + ', Rotate: ' + escapeHtml(layer.rotate || 0) + '</span>';
                } else {
                    var outlineDetail = '';
                    if (String(layer.place || '').toLowerCase() === 'fg') {
                        outlineDetail = ', Outline: ' + escapeHtml(String(layer.outline_width || layer.outline || '0')) + 'px ' + escapeHtml(layer.outline_color || '#ffffff');
                    }
                    var filterDetail = layer.image_filter && layer.image_filter !== 'normal' ? ', Filter: ' + escapeHtml(layer.image_filter) : '';
                    detail =
                        '<span>X: ' + escapeHtml(layer.x) + ', Y: ' + escapeHtml(layer.y) + '</span>' +
                        '<span>' + escapeHtml(layer.width) + ' x ' + escapeHtml(layer.height) + ' px, Scale: ' + escapeHtml(layer.scale || 100) + '%, Rotate: ' + escapeHtml(layer.rotate || 0) + outlineDetail + filterDetail + '</span>';
                }

                return '<div class="layer-card' + (isSelected ? ' is-selected' : '') + '" role="button" tabindex="0" data-layer-id="' + escapeHtml(id) + '" data-layer-type="' + escapeHtml(type) + '">' +
                    '<span class="layer-card-head"><strong>' + escapeHtml(getLayerLabel(layer)) + '</strong><span class="layer-card-actions">' +
                    '<button type="button" class="layer-move-up" title="Move layer up" ' + (canMoveUp ? '' : 'disabled') + '>Up</button>' +
                    '<button type="button" class="layer-move-down" title="Move layer down" ' + (canMoveDown ? '' : 'disabled') + '>Down</button>' +
                    '<button type="button" class="layer-bring-front" title="Bring to front" ' + (canMoveUp ? '' : 'disabled') + '>Front</button>' +
                    '<button type="button" class="layer-send-back" title="Send to back" ' + (canMoveDown ? '' : 'disabled') + '>Back</button>' +
                    '</span></span>' +
                    detail +
                    '</div>';
            }).join('');

            $list.html(html);
        }

        function moveLayer(id, type, direction) {
            getAllRenderableLayers();
            var index = layerOrder.indexOf(getLayerKey(id, type));
            var targetIndex = direction === 'up' ? index + 1 : index - 1;

            if (index < 0 || targetIndex < 0 || targetIndex >= layerOrder.length) {
                return;
            }

            recordUndoSnapshot();
            var current = layerOrder[index];
            layerOrder[index] = layerOrder[targetIndex];
            layerOrder[targetIndex] = current;
            syncLayerArrays();
            renderLayerList();
            markSelectedLayer(id, type);
        }

        function moveLayerToEdge(id, type, edge) {
            getAllRenderableLayers();
            var key = getLayerKey(id, type);
            var index = layerOrder.indexOf(key);

            if (index < 0) {
                return;
            }

            recordUndoSnapshot();
            layerOrder.splice(index, 1);
            if (edge === 'front') {
                layerOrder.push(key);
            } else {
                layerOrder.unshift(key);
            }
            syncLayerArrays();
            renderLayerList();
            markSelectedLayer(id, type);
        }

        function markSelectedLayer(id, type) {
            selectedLayerId = String(id || '');
            selectedLayerType = String(type || '');
            $('.layer-card').removeClass('is-selected');
            $('.note, .overlay, .element-layer, .template-background-layer').removeClass('is-selected-layer');
            $('.layer-card[data-layer-id="' + id + '"][data-layer-type="' + type + '"]').addClass('is-selected');
            getCanvasLayer(id, type).addClass('is-selected-layer');
            $('#deleteLayerBtn')
                .prop('disabled', type === 'background')
                .toggle(type !== 'background')
                .data('layer-id', id)
                .data('layer-type', type);
        }

        function releaseTemporaryLayerPreview() {
            $('.note, .overlay, .element-layer, .template-background-layer').removeClass('is-selected-layer');
            applyLayerStack();
        }

        function updateSelectedLayerInfo(type, title, lines) {
            var html = '<strong>' + escapeHtml(title) + '</strong>';
            lines.forEach(function(line) {
                html += '<span>' + escapeHtml(line) + '</span>';
            });
            $('#selectedLayerInfo').html(html);
        }

        function clearSelectedLayer() {
            $('.layer-card').removeClass('is-selected');
            $('.note, .overlay, .element-layer, .template-background-layer').removeClass('is-selected-layer');
            selectedLayerId = '';
            selectedLayerType = '';
            $('#textid, #imgid, #elementId, #item, #posX, #posY, #textwidth, #textheight, #textrotate, #posLeft, #posTop, #height, #width, #rotate, #imageScale, #imageOutline, #elementX, #elementY, #elementWidth, #elementHeight, #elementRotation, #elementScale').val('');
            $('#imageRelativeTo, #elementRelativeTo, #imageOffsetY, #elementOffsetY, #elementPadding').val('');
            $('#imageFilter').val('normal');
            $('#imageOutlineColor').val('#ffffff');
            $('#imagePlace, #textalign, #fontstyle, #fontweight, #fontfamily').val('');
            $('#saveTextBtn').text('Add Text');
            $('#saveImg').text('Add Image');
            hideGuideLines();
            $('#deleteLayerBtn').prop('disabled', true).hide().removeData('layer-id').removeData('layer-type');
            updateSelectedLayerInfo('', 'No layer selected', [
                'Select a text or image layer from the left list or canvas.'
            ]);
        }

        function selectBackgroundLayer() {
            var $background = getCanvasLayer('template-background', 'background');

            markSelectedLayer('template-background', 'background');
            $('#textid, #imgid, #elementId').val('');
            updateSelectedLayerInfo('background', 'Selected background image', [
                'Place: Template canvas',
                'X: 0, Y: 0',
                'Size: Full canvas',
                'Use Up/Down from layer list to change stacking'
            ]);
            updateGuideLines($background);
        }

        function removeLayerData(id, type) {
            updateList = updateList.filter(function(layer) {
                return !(parseInt(layer.id, 10) === parseInt(id, 10) && layer.type === type);
            });
            layerOrder = layerOrder.filter(function(key) {
                return key !== getLayerKey(id, type);
            });

            if (typeof updateImage !== 'undefined' && Array.isArray(updateImage)) {
                updateImage = updateImage.filter(function(layer) {
                    return !(parseInt(layer.id, 10) === parseInt(id, 10) && layer.type === type);
                });
            }
        }

        function deleteSelectedLayer() {
            var id = $('#deleteLayerBtn').data('layer-id');
            var type = $('#deleteLayerBtn').data('layer-type');

            if (!id || !type) {
                alert('Please select a layer first');
                return;
            }

            if (!confirm('Delete selected layer?')) {
                return;
            }

            recordUndoSnapshot();
            getCanvasLayer(id, type).remove();
            removeLayerData(id, type);
            syncLayerArrays();
            renderLayerList();
            clearSelectedLayer();
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function(char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        function refreshLayerCard(id, type) {
            var $card = $('.layer-card[data-layer-id="' + id + '"][data-layer-type="' + type + '"]');
            var layer = findLayerData(id, type);

            if (!$card.length || !layer.id) {
                renderLayerList();
                return;
            }

            renderLayerList();
        }

        function rgbToHex(color) {
            var match = String(color || '').match(/\d+/g);
            if (!match || match.length < 3) {
                return normalizeHexColor(color);
            }

            return '#' + match.slice(0, 3).map(function(value) {
                var hex = parseInt(value, 10).toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            }).join('').toUpperCase();
        }

        function normalizeHexColor(color) {
            color = String(color || '').trim();

            if (!color) {
                return '#FF0000';
            }

            if (color.charAt(0) !== '#') {
                color = '#' + color;
            }

            if (/^#[0-9a-f]{3}$/i.test(color)) {
                color = '#' + color.charAt(1) + color.charAt(1) + color.charAt(2) + color.charAt(2) + color.charAt(3) + color.charAt(3);
            }

            if (!/^#[0-9a-f]{6}$/i.test(color)) {
                return '#FF0000';
            }

            return color.toUpperCase();
        }

        function fitTextLayerHeight($note) {
            $note.css({
                height: 'auto',
                'line-height': '.9',
                margin: 0,
                'max-width': 'none',
                'min-width': '1px',
                overflow: 'hidden',
                'overflow-wrap': 'anywhere',
                padding: 0,
                'white-space': 'normal',
                'word-break': 'break-word'
            });

            var fontSize = parseFloat($note.css('font-size')) || 24;
            var lineHeight = Math.ceil(fontSize * 0.9);
            var naturalHeight = Math.max(lineHeight, Math.ceil($note[0].scrollHeight || lineHeight));
            $note.css('height', naturalHeight + 'px');
            return naturalHeight;
        }

        function selectTextLayer(id) {
            var layer = findLayerData(id, 'text');
            var $note = getCanvasLayer(id, 'text');

            if (!$note.length) {
                return;
            }

            markSelectedLayer(id, 'text');
            $('#textid').val(id);
            $('#imgid, #elementId').val('');
            $('#item').val($.trim(layer.text || $note.text()));
            $('#posX').val(Math.round(parseFloat($note.css('left')) || layer.x || 0));
            $('#posY').val(Math.round(parseFloat($note.css('top')) || layer.y || 0));
            $('#textalign').val(layer.textalign || $note.css('text-align') || '');
            $('#fontstyle').val(layer.fontstyle || $note.css('font-style') || '');
            $('#fontcolor').val(normalizeHexColor(layer.color || rgbToHex($note.css('color')) || '#FF0000'));
            $('#fontweight').val(layer.fontweight || $note.css('font-weight') || '');
            $('#fontsize').val(layer.fontsize || parseInt($note.css('font-size'), 10) || '');
            $('#fontfamily').val(layer.fontfamily || '');
            $('#textwidth').val(layer.width || Math.round($note.outerWidth()));
            $('#textrotate').val(layer.rotate || getLayerRotation($note) || 0);
            refreshFormatToggles();
            var fittedHeight = fitTextLayerHeight($note);
            $('#textheight').val(fittedHeight);
            if (layer.id) {
                layer.height = fittedHeight;
            }
            $('#saveTextBtn').text(layer.id ? 'Update Text' : 'Add Text');
            updateSelectedLayerInfo('text', 'Selected text layer', [
                'Text: ' + $.trim(layer.text || $note.text()),
                'X: ' + $('#posX').val() + ', Y: ' + $('#posY').val(),
                'Color: ' + $('#fontcolor').val() + ', Size: ' + $('#fontsize').val() + ', Rotate: ' + $('#textrotate').val(),
                'Layer ID: ' + id
            ]);
            updateGuideLines($note);
        }

        function getLayerRotation($element) {
            var transform = $element.css('transform');

            if (!transform || transform === 'none') {
                return 0;
            }

            var values = transform.split('(')[1].split(')')[0].split(',');
            if (values.length < 2) {
                return 0;
            }

            return Math.round(Math.atan2(values[1], values[0]) * (180 / Math.PI));
        }

        function getImageOutlineValue() {
            var place = String($('#imagePlace').val() || '').toLowerCase();
            var outline = parseInt($('#imageOutline').val(), 10) || 0;

            return place === 'fg' ? Math.max(0, Math.min(80, outline)) : 0;
        }

        function getImageOutlineColorValue() {
            var color = $('#imageOutlineColor').val() || '#ffffff';

            return /^#[0-9a-f]{6}$/i.test(color) ? color : '#ffffff';
        }

        function toggleImageOutlineControl() {
            var isFg = String($('#imagePlace').val() || '').toLowerCase() === 'fg';

            $('.image-outline-row').toggle(isFg);
            if (!isFg) {
                $('#imageOutline').val(0);
                $('#imageOutlineColor').val('#ffffff');
            } else if ($('#imageOutline').val() === '') {
                $('#imageOutline').val(0);
            }
        }

        function selectImageLayer(id) {
            var layer = findLayerData(id, 'image');
            var $overlay = getCanvasLayer(id, 'image');

            $('#imageRelativeTo').val(layer.relative_to || '');
            $('#imageOffsetY').val(layer.offset_y !== undefined ? layer.offset_y : 15);

            if (!$overlay.length) {
                markSelectedLayer(id, 'image');
                $('#imgid').val(id);
                $('#textid, #elementId').val('');
                $('#imagePlace').val(layer.place || '');
                $('#posLeft').val(layer.x || 0);
                $('#posTop').val(layer.y || 0);
                $('#height').val(layer.height || 100);
                $('#width').val(layer.width || 100);
                $('#rotate').val(layer.rotate || 0);
                $('#imageScale').val(layer.scale || 100);
                $('#imageFilter').val(layer.image_filter || layer.filter || 'normal');
                $('#imageOutline').val(layer.outline_width || layer.outline || 0);
                $('#imageOutlineColor').val(layer.outline_color || '#ffffff');
                toggleImageOutlineControl();
                $('#saveImg').text(layer.id ? 'Update Image' : 'Add Image');
                updateSelectedLayerInfo('image', 'Selected image layer', [
                    'Place: ' + (layer.image_src ? 'upload' : ($('#imagePlace').val() || 'Not set')),
                    'X: ' + $('#posLeft').val() + ', Y: ' + $('#posTop').val(),
                    'Size: ' + $('#width').val() + ' x ' + $('#height').val() + ', Scale: ' + $('#imageScale').val() + '%, Rotate: ' + $('#rotate').val() + ', Filter: ' + getImageFilterValue() + ', Outline: ' + getImageOutlineValue() + 'px ' + getImageOutlineColorValue(),
                    'Layer ID: ' + id
                ]);
                return;
            }

            markSelectedLayer(id, 'image');
            $('#imgid').val(id);
            $('#textid, #elementId').val('');
            $('#imagePlace').val(layer.place || '');
            $('#posLeft').val(Math.floor(parseFloat($overlay.css('left')) || layer.x || 0));
            $('#posTop').val(Math.floor(parseFloat($overlay.css('top')) || layer.y || 0));
            $('#height').val(layer.height || Math.round($overlay.outerHeight()));
            $('#width').val(layer.width || Math.round($overlay.outerWidth()));
            $('#imageScale').val(layer.scale || 100);
            $('#imageFilter').val(layer.image_filter || layer.filter || 'normal');
            $('#imageOutline').val(layer.outline_width || layer.outline || 0);
            $('#imageOutlineColor').val(layer.outline_color || '#ffffff');
            toggleImageOutlineControl();
            var currentRotate = layer.rotate;
            if (currentRotate === undefined || currentRotate === null || currentRotate === '') {
                var transform = $overlay.css('transform');
                currentRotate = 0;
                if (transform && transform !== 'none') {
                    var values = transform.split('(')[1].split(')')[0].split(',');
                    currentRotate = Math.round(Math.atan2(values[1], values[0]) * (180 / Math.PI));
                }
            }
            $('#rotate').val(currentRotate || 0);
            $('#saveImg').text(layer.id ? 'Update Image' : 'Add Image');
            updateSelectedLayerInfo('image', 'Selected image layer', [
                'Place: ' + (layer.image_src ? 'upload' : ($('#imagePlace').val() || 'Not set')),
                'X: ' + $('#posLeft').val() + ', Y: ' + $('#posTop').val(),
                'Size: ' + $('#width').val() + ' x ' + $('#height').val() + ', Scale: ' + $('#imageScale').val() + '%, Rotate: ' + $('#rotate').val() + ', Filter: ' + getImageFilterValue() + ', Outline: ' + getImageOutlineValue() + 'px ' + getImageOutlineColorValue(),
                'Layer ID: ' + id
            ]);
            updateGuideLines($overlay);
        }

        function syncTextLayerPosition($note, ui, preserveWidth) {
            var id = $note.attr('id');
            var left = ui ? ui.position.left : parseFloat($note.css('left')) || 0;
            var top = ui ? ui.position.top : parseFloat($note.css('top')) || 0;
            var layer = findLayerData(id, 'text');
            var currentWidth = preserveWidth ? Math.round(parseFloat($note.css('width')) || $note.outerWidth()) : Math.round($note.outerWidth());

            markSelectedLayer(id, 'text');
            $('#textid').val(id);
            $('#imgid, #elementId').val('');
            $('#posX').val(Math.round(left));
            $('#posY').val(Math.round(top));
            $('#item').val($.trim($note.text()));
            $('#textwidth').val(currentWidth);
            $('#textrotate').val($('#textrotate').val() || layer.rotate || getLayerRotation($note) || 0);
            var fittedHeight = fitTextLayerHeight($note);
            $('#textheight').val(fittedHeight);
            $('#saveTextBtn').text('Update Text');
            if (layer.id) {
                layer.text = $.trim($note.text());
                layer.x = $('#posX').val();
                layer.y = $('#posY').val();
                layer.width = currentWidth;
                layer.height = $('#textheight').val();
                layer.rotate = $('#textrotate').val() || 0;
                layer.color = $('#fontcolor').val() || rgbToHex($note.css('color'));
                layer.fontsize = $('#fontsize').val() || parseInt($note.css('font-size'), 10);

                // Reposition dependent child layers
                updateList.forEach(function(l) {
                    if ((l.type === 'image' || l.type === 'element') && String(l.relative_to) === String(id)) {
                        var parentY = parseFloat(layer.y) || 0;
                        var parentH = parseFloat(layer.height) || 0;
                        var offsetY = parseFloat(l.offset_y) || 0;
                        var newY = Math.round(parentY + parentH + offsetY);
                        l.y = newY;
                        
                        var $dep = getCanvasLayer(l.id, l.type);
                        if ($dep.length) {
                            $dep.css('top', newY + 'px');
                        }
                        if (selectedLayerId === String(l.id) && selectedLayerType === l.type) {
                            if (l.type === 'image') $('#posTop').val(newY);
                            if (l.type === 'element') $('#elementY').val(newY);
                        }
                    }
                });
            }
            refreshLayerCard(id, 'text');
            updateSelectedLayerInfo('text', 'Selected text layer', [
                'Text: ' + $.trim($note.text()),
                'X: ' + $('#posX').val() + ', Y: ' + $('#posY').val(),
                'Color: ' + ($('#fontcolor').val() || rgbToHex($note.css('color'))) + ', Size: ' + ($('#fontsize').val() || parseInt($note.css('font-size'), 10)) + ', Rotate: ' + ($('#textrotate').val() || 0),
                'Layer ID: ' + id
            ]);
            updateGuideLines($note);
        }

        function applyTextFieldsToSelected() {
            var id = $('#textid').val();
            var $note = getCanvasLayer(id, 'text');

            if (!id || !$note.length) {
                return;
            }

            var layer = findLayerData(id, 'text');
            var text = $('#item').val();
            var x = $('#posX').val() || 0;
            var y = $('#posY').val() || 0;
            var width = parseFloat($('#textwidth').val());
            if (!width || width < 1) {
                width = Math.round($note.outerWidth()) || 1;
            }
            var fontSize = $('#fontsize').val();
            var color = $('#fontcolor').val();
            var textAlign = $('#textalign').val();
            var fontStyle = $('#fontstyle').val();
            var fontWeight = $('#fontweight').val();
            var fontFamily = $('#fontfamily').val();
            var rotate = $('#textrotate').val() || 0;

            $note.text(text);
            $note.css({
                left: x + 'px',
                top: y + 'px',
                width: width + 'px',
                height: 'auto',
                color: color,
                'text-align': textAlign,
                'font-style': fontStyle,
                'font-weight': fontWeight,
                'font-size': fontSize ? fontSize + 'px' : '',
                'font-family': fontFamily,
                transform: 'rotate(' + rotate + 'deg)',
                'max-width': 'none',
                'min-width': '1px',
                'overflow-wrap': 'anywhere',
                'white-space': 'normal',
                'word-break': 'break-word'
            });
            var height = fitTextLayerHeight($note);
            $('#textheight').val(height);

            if (layer.id) {
                layer.text = text;
                layer.x = x;
                layer.y = y;
                layer.width = width;
                layer.height = height;
                layer.color = color;
                layer.textalign = textAlign;
                layer.fontstyle = fontStyle;
                layer.fontweight = fontWeight;
                layer.fontsize = fontSize;
                layer.fontfamily = fontFamily;
                layer.rotate = rotate;
            }

            refreshLayerCard(id, 'text');
            syncTextLayerPosition($note, null, true);
        }

        function syncImageLayerPosition($overlay, ui) {
            var id = $overlay.attr('id');
            var left = ui ? ui.position.left : parseFloat($overlay.css('left')) || 0;
            var top = ui ? ui.position.top : parseFloat($overlay.css('top')) || 0;
            var layer = findLayerData(id, 'image');

            markSelectedLayer(id, 'image');
            $('#imgid').val(id);
            $('#textid, #elementId').val('');
            $('#posLeft').val(Math.floor(left));
            $('#posTop').val(Math.floor(top));
            $('#height').val(Math.round($overlay.outerHeight()));
            $('#width').val(Math.round($overlay.outerWidth()));
            $('#imageScale').val($('#imageScale').val() || layer.scale || 100);
            $('#imageFilter').val(layer.image_filter || layer.filter || 'normal');
            $('#imageOutline').val(layer.outline_width || layer.outline || 0);
            $('#imageOutlineColor').val(layer.outline_color || '#ffffff');
            toggleImageOutlineControl();
            $('#saveImg').text('Update Image');
            if (layer.id) {
                layer.x = $('#posLeft').val();
                layer.y = $('#posTop').val();
                layer.height = $('#height').val();
                layer.width = $('#width').val();
                layer.rotate = $('#rotate').val() || 0;
                layer.place = $('#imagePlace').val();
                layer.scale = $('#imageScale').val() || 100;
                layer.image_filter = getImageFilterValue();
                layer.outline_width = getImageOutlineValue();
                layer.outline_color = getImageOutlineValue() ? getImageOutlineColorValue() : '#ffffff';
                layer.baseWidth = layer.baseWidth || $overlay.data('base-width') || layer.width;
                layer.baseHeight = layer.baseHeight || $overlay.data('base-height') || layer.height;
                layer.image_src = layer.image_src || '';
                layer.image_url = layer.image_url || '';

                var relativeTo = $('#imageRelativeTo').val() || layer.relative_to || '';
                var offsetY = layer.offset_y;
                if (relativeTo) {
                    var parentLayer = findLayerData(relativeTo, 'text');
                    if (parentLayer) {
                        var parentY = parseFloat(parentLayer.y) || 0;
                        var parentH = parseFloat(parentLayer.height) || 0;
                        var currentY = parseFloat(layer.y) || 0;
                        offsetY = Math.round(currentY - (parentY + parentH));
                        layer.offset_y = offsetY;
                        $('#imageOffsetY').val(offsetY);
                    }
                }
                layer.relative_to = relativeTo;
            }
            refreshLayerCard(id, 'image');
            updateSelectedLayerInfo('image', 'Selected image layer', [
                'Place: ' + (layer.image_src ? 'upload' : ($('#imagePlace').val() || 'Not set')),
                'X: ' + $('#posLeft').val() + ', Y: ' + $('#posTop').val(),
                'Size: ' + $('#width').val() + ' x ' + $('#height').val() + ', Scale: ' + ($('#imageScale').val() || 100) + '%, Rotate: ' + ($('#rotate').val() || 0) + ', Filter: ' + getImageFilterValue() + ', Outline: ' + getImageOutlineValue() + 'px ' + getImageOutlineColorValue(),
                'Layer ID: ' + id
            ]);
            updateGuideLines($overlay);
        }

        function applyImageFieldsToSelected() {
            var id = $('#imgid').val();
            var $overlay = getCanvasLayer(id, 'image');

            if (!id || !$overlay.length) {
                return;
            }

            var layer = findLayerData(id, 'image');
            var imageLayer = updateImage.find(function(item) {
                return parseInt(item.id, 10) === parseInt(id, 10) && item.type === 'image';
            }) || layer;
            var x = $('#posLeft').val() || 0;
            var y = $('#posTop').val() || 0;
            var height = $('#height').val() || 100;
            var width = $('#width').val() || 100;
            var rotate = $('#rotate').val() || 0;
            var place = $('#imagePlace').val();
            var scale = $('#imageScale').val() || 100;
            var imageFilter = getImageFilterValue();
            var outlineWidth = getImageOutlineValue();
            var outlineColor = outlineWidth ? getImageOutlineColorValue() : '#ffffff';
            var baseWidth = parseFloat(layer.baseWidth || imageLayer.baseWidth || $overlay.data('base-width') || width) || 100;
            var baseHeight = parseFloat(layer.baseHeight || imageLayer.baseHeight || $overlay.data('base-height') || height) || 100;

            if ($(document.activeElement).attr('id') === 'imageScale') {
                width = Math.max(1, Math.round(baseWidth * (parseFloat(scale) || 100) / 100));
                height = Math.max(1, Math.round(baseHeight * (parseFloat(scale) || 100) / 100));
                $('#width').val(width);
                $('#height').val(height);
            } else {
                scale = Math.max(1, Math.round((parseFloat(width) || 1) / baseWidth * 100));
                $('#imageScale').val(scale);
            }

            $overlay.css({
                left: x + 'px',
                top: y + 'px',
                height: height + 'px',
                width: width + 'px',
                transform: 'rotate(' + rotate + 'deg)',
                filter: getImageCssFilter(imageFilter),
                border: '2px solid ' + (String(place).toLowerCase() === 'fg' ? 'red' : 'white')
            });
            $overlay.data('base-width', baseWidth);
            $overlay.data('base-height', baseHeight);

            var relativeTo = $('#imageRelativeTo').val() || '';
            var offsetY = $('#imageOffsetY').val() !== '' ? parseInt($('#imageOffsetY').val(), 10) : 15;
            if (isNaN(offsetY)) offsetY = 15;

            if (layer.id) {
                layer.x = x;
                layer.y = y;
                layer.height = height;
                layer.width = width;
                layer.rotate = rotate;
                layer.place = place;
                layer.scale = scale;
                layer.image_filter = imageFilter;
                layer.outline_width = outlineWidth;
                layer.outline_color = outlineColor;
                layer.baseWidth = baseWidth;
                layer.baseHeight = baseHeight;
                layer.relative_to = relativeTo;
                layer.offset_y = offsetY;
            }

            if (imageLayer.id) {
                imageLayer.x = x;
                imageLayer.y = y;
                imageLayer.height = height;
                imageLayer.width = width;
                imageLayer.rotate = rotate;
                imageLayer.place = place;
                imageLayer.scale = scale;
                imageLayer.image_filter = imageFilter;
                imageLayer.outline_width = outlineWidth;
                imageLayer.outline_color = outlineColor;
                imageLayer.baseWidth = baseWidth;
                imageLayer.baseHeight = baseHeight;
                imageLayer.relative_to = relativeTo;
                imageLayer.offset_y = offsetY;
                imageLayer.image_src = layer.image_src || imageLayer.image_src || '';
                imageLayer.image_url = layer.image_url || imageLayer.image_url || '';
            }

            refreshLayerCard(id, 'image');
            syncImageLayerPosition($overlay);
            syncLayerArrays();
        }

        function addUploadedImageLayer(fileData) {
            var $canvas = getActiveCanvas();

            if (!$canvas.length || !fileData || !fileData.filename) {
                return;
            }

            recordUndoSnapshot();
            var defaultWidth = 220;
            var defaultHeight = 220;
            var x = Math.max(0, Math.round(($canvas.outerWidth() - defaultWidth) / 2));
            var y = Math.max(0, Math.round(($canvas.outerHeight() - defaultHeight) / 2));
            var id = Date.now() + Math.floor(Math.random() * 1000);
            var layer = {
                id: id,
                type: 'image',
                x: x,
                y: y,
                text: 'uploaded image',
                place: 'upload',
                height: defaultHeight,
                width: defaultWidth,
                rotate: 0,
                scale: 100,
                image_filter: 'normal',
                outline_width: 0,
                outline_color: '#ffffff',
                baseWidth: defaultWidth,
                baseHeight: defaultHeight,
                image_src: fileData.filename,
                image_url: fileData.url || '',
                reusable: !!fileData.reusable
            };

            updateList.push(layer);
            updateImage.push(layer);
            addLayerToOrder(layer.id, layer.type);
            $canvas.append(buildImageLayerElement(layer));
            enableImageMovement(getCanvasLayer(layer.id, 'image'));
            syncLayerArrays();
            renderLayerList();
            selectImageLayer(layer.id);
        }

        var reusableLayerImagesCache = [];
        var pendingConvertFile = null;
        var pendingConvertImage = null;
        var pendingConvertObjectUrl = '';
        var pendingConvertBlob = null;

        function isWebpFile(file) {
            return file && (/\.webp$/i.test(file.name || '') || file.type === 'image/webp');
        }

        function formatFileSize(bytes) {
            if (!bytes) {
                return '0 KB';
            }
            if (bytes >= 1048576) {
                return (bytes / 1048576).toFixed(2) + ' MB';
            }
            return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        function getWebpFilename(filename) {
            var safeName = String(filename || 'layer-image').replace(/\.[^.]+$/, '');
            return safeName + '.webp';
        }

        function updateImageConvertPreview() {
            if (!pendingConvertImage) {
                return;
            }

            var width = Math.max(1, parseInt($('#imageConvertWidth').val(), 10) || pendingConvertImage.naturalWidth || pendingConvertImage.width);
            var height = Math.max(1, parseInt($('#imageConvertHeight').val(), 10) || pendingConvertImage.naturalHeight || pendingConvertImage.height);
            var quality = Math.max(20, Math.min(100, parseInt($('#imageConvertQuality').val(), 10) || 86));
            var canvas = $('#imageConvertCanvas')[0];
            var context = canvas.getContext('2d');

            $('#imageConvertQualityText').text(quality + '%');
            canvas.width = width;
            canvas.height = height;
            context.clearRect(0, 0, width, height);
            context.drawImage(pendingConvertImage, 0, 0, width, height);

            canvas.toBlob(function(blob) {
                pendingConvertBlob = blob;
                $('#imageConvertOutputMeta').text(blob ? ('Output: WebP, ' + formatFileSize(blob.size)) : 'Output: WebP');
            }, 'image/webp', quality / 100);
        }

        function closeImageConvertPopup(clearInput) {
            $('#imageConvertPopup').removeClass('is-open').attr('aria-hidden', 'true');

            if (pendingConvertObjectUrl) {
                URL.revokeObjectURL(pendingConvertObjectUrl);
            }

            pendingConvertFile = null;
            pendingConvertImage = null;
            pendingConvertObjectUrl = '';
            pendingConvertBlob = null;

            if (clearInput) {
                $('#layerImageUpload').val('');
            }
        }

        function openImageConvertPopup(file) {
            if (!file) {
                return;
            }

            closeImageConvertPopup(false);
            pendingConvertFile = file;
            pendingConvertObjectUrl = URL.createObjectURL(file);
            pendingConvertImage = new Image();

            pendingConvertImage.onload = function() {
                $('#imageConvertWidth').val(pendingConvertImage.naturalWidth || pendingConvertImage.width);
                $('#imageConvertHeight').val(pendingConvertImage.naturalHeight || pendingConvertImage.height);
                $('#imageConvertQuality').val(86);
                $('#imageConvertMeta').text('Source: ' + file.name + ', ' + formatFileSize(file.size));
                $('#imageConvertPopup').addClass('is-open').attr('aria-hidden', 'false');
                updateImageConvertPreview();
            };

            pendingConvertImage.onerror = function() {
                alert('Image preview failed');
                closeImageConvertPopup(true);
            };

            pendingConvertImage.src = pendingConvertObjectUrl;
        }

        function uploadConvertedLayerImage() {
            if (!pendingConvertFile || !pendingConvertImage) {
                return;
            }

            var canvas = $('#imageConvertCanvas')[0];
            var quality = Math.max(20, Math.min(100, parseInt($('#imageConvertQuality').val(), 10) || 86)) / 100;

            canvas.toBlob(function(blob) {
                if (!blob) {
                    alert('WebP conversion failed');
                    return;
                }

                var webpFile = new File([blob], getWebpFilename(pendingConvertFile.name), {
                    type: 'image/webp',
                    lastModified: Date.now()
                });

                closeImageConvertPopup(false);
                uploadLayerImageFile(webpFile);
            }, 'image/webp', quality);
        }

        function renderReusableLayerSelection(file) {
            var $thumb = $('#reusableLayerSelectedThumb');
            var $empty = $('#reusableLayerEmptyThumb');
            var $name = $('#reusableLayerSelectedName');

            if (!file || !file.filename) {
                $('#reusableLayerImageSelect').val('');
                $thumb.hide().attr('src', '');
                $empty.show();
                $name.text('Reusable images');
                return;
            }

            $('#reusableLayerImageSelect').val(file.filename);
            $thumb.attr('src', file.url).attr('alt', file.label || file.filename).show();
            $empty.hide();
            $name.text(file.label || file.filename);
        }

        function selectReusableLayerImage(filename) {
            var selected = reusableLayerImagesCache.find(function(file) {
                return file.filename === filename;
            });

            renderReusableLayerSelection(selected || null);
            renderReusableLayerGallery();
        }

        function renderReusableLayerGallery() {
            var $grid = $('#reuseGalleryGrid');
            var selectedFilename = $('#reusableLayerImageSelect').val();
            $grid.empty();

            if (!reusableLayerImagesCache.length) {
                $grid.append('<div class="reuse-gallery-empty">No reusable image found</div>');
                return;
            }

            reusableLayerImagesCache.forEach(function(file) {
                var $item = $('<button/>', {
                    type: 'button',
                    class: 'reuse-gallery-item' + (file.filename === selectedFilename ? ' is-selected' : ''),
                    'data-filename': file.filename
                });

                $item.append($('<img/>', {
                    src: file.url,
                    alt: file.label || file.filename
                }));
                $item.append($('<span/>').text(file.label || file.filename));
                $grid.append($item);
            });
        }

        function openReusableLayerGallery() {
            renderReusableLayerGallery();
            $('#reuseGalleryPopup').addClass('is-open').attr('aria-hidden', 'false');
        }

        function closeReusableLayerGallery() {
            $('#reuseGalleryPopup').removeClass('is-open').attr('aria-hidden', 'true');
        }

        function loadReusableLayerImages() {
            $.ajax({
                url: "<?= Url::toRoute(['product/reusable-layer-images']) ?>",
                method: 'GET',
                success: function(response) {
                    var $select = $('#reusableLayerImageSelect');
                    $select.html('<option value="">Reusable images</option>');
                    reusableLayerImagesCache = [];

                    if (!response || !response.success || !response.files) {
                        renderReusableLayerSelection(null);
                        renderReusableLayerGallery();
                        return;
                    }

                    reusableLayerImagesCache = response.files;
                    response.files.forEach(function(file) {
                        $select.append(
                            $('<option/>')
                                .val(file.filename)
                                .text(file.label || file.filename)
                                .attr('data-url', file.url)
                        );
                    });

                    renderReusableLayerSelection(response.files[0] || null);
                    renderReusableLayerGallery();
                }
            });
        }

        function uploadLayerImageFile(file) {
            var formData = new FormData();
            formData.append('layer_image', file);
            formData.append('save_for_reuse', $('#saveLayerImageForReuse').is(':checked') ? '1' : '0');
            formData.append('_csrf-backend', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: "<?= Url::toRoute(['product/upload-layer-image']) ?>",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (!response || !response.success) {
                        alert(response && response.message ? response.message : 'Image upload failed');
                        return;
                    }

                    addUploadedImageLayer(response);
                    $('#layerImageUpload').val('');
                    if (response.reusable) {
                        loadReusableLayerImages();
                    }
                },
                error: function() {
                    alert('Image upload failed');
                }
            });
        }

        function uploadLayerImage() {
            var input = $('#layerImageUpload')[0];

            if (!input || !input.files || !input.files.length) {
                alert('Please select an image');
                return;
            }

            var file = input.files[0];

            if (isWebpFile(file)) {
                uploadLayerImageFile(file);
                return;
            }

            if (/^image\/(png|jpe?g)$/i.test(file.type) || /\.(png|jpe?g)$/i.test(file.name || '')) {
                openImageConvertPopup(file);
                return;
            }

            alert('Please upload PNG, JPG, JPEG, or WebP image');
        }

        function addReusableLayerImage() {
            var $selected = $('#reusableLayerImageSelect option:selected');
            var filename = $selected.val();
            var selectedFile = reusableLayerImagesCache.find(function(file) {
                return file.filename === filename;
            });

            if (!filename) {
                alert('Please select a reusable image');
                return;
            }

            addUploadedImageLayer({
                filename: filename,
                url: (selectedFile && selectedFile.url) || $selected.data('url') || ('<?= Yii::getAlias('@storageUrl') ?>/images/' + filename),
                reusable: true
            });
        }

        function selectElementLayer(id) {
            var layer = findLayerData(id, 'element');
            var $element = getCanvasLayer(id, 'element');

            if (!$element.length) {
                return;
            }

            $('#elementRelativeTo').val(layer.relative_to || '');
            $('#elementOffsetY').val(layer.offset_y !== undefined ? layer.offset_y : 15);
            $('#elementPadding').val(layer.padding !== undefined ? layer.padding : '');

            markSelectedLayer(id, 'element');
            $('#elementId').val(id);
            $('#textid, #imgid').val('');
            $('#elementX').val(Math.round(parseFloat($element.css('left')) || layer.x || 0));
            $('#elementY').val(Math.round(parseFloat($element.css('top')) || layer.y || 0));
            $('#elementWidth').val(layer.width || Math.round($element.outerWidth()));
            $('#elementHeight').val(layer.height || Math.round($element.outerHeight()));
            $('#elementRotation').val(layer.rotation || layer.rotate || 0);
            $('#elementColor').val(normalizeHexColor(layer.color || '#60a5fa'));
            $('#elementOpacity').val(layer.opacity || 1);
            $('#elementBorderColor').val(normalizeHexColor(layer.border_color || '#1d4ed8'));
            $('#elementBorderWidth').val(layer.border_width || 0);
            $('#elementScale').val(layer.scale || 100);
            updateSelectedLayerInfo('element', 'Selected element layer', [
                'Type: ' + (layer.element_type || 'element'),
                'X: ' + $('#elementX').val() + ', Y: ' + $('#elementY').val(),
                'Size: ' + $('#elementWidth').val() + ' x ' + $('#elementHeight').val() + ', Scale: ' + $('#elementScale').val() + '%, Rotate: ' + $('#elementRotation').val(),
                'Layer ID: ' + id
            ]);
            updateGuideLines($element);
        }

        function syncElementLayerPosition($element, ui) {
            var id = $element.attr('id');
            var left = ui ? ui.position.left : parseFloat($element.css('left')) || 0;
            var top = ui ? ui.position.top : parseFloat($element.css('top')) || 0;
            var layer = findLayerData(id, 'element');

            markSelectedLayer(id, 'element');
            $('#elementId').val(id);
            $('#textid, #imgid').val('');
            $('#elementX').val(Math.floor(left));
            $('#elementY').val(Math.floor(top));
            $('#elementWidth').val(Math.round($element.outerWidth()));
            $('#elementHeight').val(Math.round($element.outerHeight()));

            if (layer.id) {
                layer.x = $('#elementX').val();
                layer.y = $('#elementY').val();
                layer.width = $('#elementWidth').val();
                layer.height = $('#elementHeight').val();
                layer.rotation = $('#elementRotation').val() || layer.rotation || 0;
                layer.z_index = getLayerZ(layer);

                var relativeTo = $('#elementRelativeTo').val() || layer.relative_to || '';
                var offsetY = layer.offset_y;
                if (relativeTo) {
                    var parentLayer = findLayerData(relativeTo, 'text');
                    if (parentLayer) {
                        var parentY = parseFloat(parentLayer.y) || 0;
                        var parentH = parseFloat(parentLayer.height) || 0;
                        var currentY = parseFloat(layer.y) || 0;
                        offsetY = Math.round(currentY - (parentY + parentH));
                        layer.offset_y = offsetY;
                        $('#elementOffsetY').val(offsetY);
                    }
                }
                layer.relative_to = relativeTo;
            }

            refreshLayerCard(id, 'element');
            updateGuideLines($element);
        }

        function applyElementFieldsToSelected() {
            var id = $('#elementId').val();
            var $element = getCanvasLayer(id, 'element');

            if (!id || !$element.length) {
                return;
            }

            var layer = findLayerData(id, 'element');
            var x = $('#elementX').val() || 0;
            var y = $('#elementY').val() || 0;
            var width = parseFloat($('#elementWidth').val()) || 120;
            var height = parseFloat($('#elementHeight').val()) || 120;
            var rotation = $('#elementRotation').val() || 0;
            var color = $('#elementColor').val() || '#60a5fa';
            var opacity = $('#elementOpacity').val() || 1;
            var borderColor = $('#elementBorderColor').val() || '#1d4ed8';
            var borderWidth = $('#elementBorderWidth').val() || 0;
            var scale = $('#elementScale').val() || 100;
            var baseWidth = parseFloat(layer.baseWidth || width) || 120;
            var baseHeight = parseFloat(layer.baseHeight || height) || 120;

            if ($(document.activeElement).attr('id') === 'elementScale') {
                width = Math.max(1, Math.round(baseWidth * (parseFloat(scale) || 100) / 100));
                height = Math.max(1, Math.round(baseHeight * (parseFloat(scale) || 100) / 100));
                $('#elementWidth').val(width);
                $('#elementHeight').val(height);
            } else {
                scale = Math.max(1, Math.round((parseFloat(width) || 1) / baseWidth * 100));
                $('#elementScale').val(scale);
            }

            var relativeTo = $('#elementRelativeTo').val() || '';
            var offsetY = $('#elementOffsetY').val() !== '' ? parseInt($('#elementOffsetY').val(), 10) : 15;
            if (isNaN(offsetY)) offsetY = 15;
            var paddingVal = $('#elementPadding').val();
            var padding = paddingVal !== '' ? parseInt(paddingVal, 10) : undefined;
            if (padding !== undefined && isNaN(padding)) padding = undefined;

            if (layer.id) {
                layer.x = x;
                layer.y = y;
                layer.width = width;
                layer.height = height;
                layer.rotation = rotation;
                layer.rotate = rotation;
                layer.color = color;
                layer.opacity = opacity;
                layer.border_color = borderColor;
                layer.border_width = borderWidth;
                layer.scale = scale;
                layer.baseWidth = baseWidth;
                layer.baseHeight = baseHeight;
                layer.z_index = getLayerZ(layer);
                layer.relative_to = relativeTo;
                layer.offset_y = offsetY;
                if (padding !== undefined) {
                    layer.padding = padding;
                } else {
                    delete layer.padding;
                }
            }

            applyLayerDataToElement(layer);
            refreshLayerCard(id, 'element');
            syncLayerArrays();
            updateSelectedLayerInfo('element', 'Selected element layer', [
                'Type: ' + (layer.element_type || 'element'),
                'X: ' + x + ', Y: ' + y,
                'Size: ' + width + ' x ' + height + ', Scale: ' + scale + '%, Rotate: ' + rotation,
                'Layer ID: ' + id
            ]);
        }

        function addElementLayer(elementType) {
            var $canvas = getActiveCanvas();

            if (!$canvas.length) {
                return;
            }

            recordUndoSnapshot();
            elementType = elementType || 'rectangle';
            var defaultWidth = elementType === 'line' ? 180 : 120;
            var defaultHeight = elementType === 'line' ? 4 : 120;
            var x = Math.max(0, Math.round(($canvas.outerWidth() - defaultWidth) / 2));
            var y = Math.max(0, Math.round(($canvas.outerHeight() - defaultHeight) / 2));
            var id = Date.now() + Math.floor(Math.random() * 1000);
            var layer = {
                id: id,
                type: 'element',
                element_type: elementType,
                x: x,
                y: y,
                width: defaultWidth,
                height: defaultHeight,
                rotation: 0,
                rotate: 0,
                color: '#60a5fa',
                opacity: 1,
                border_color: '#1d4ed8',
                border_width: 2,
                scale: 100,
                baseWidth: defaultWidth,
                baseHeight: defaultHeight,
                z_index: 0
            };

            updateList.push(layer);
            addLayerToOrder(layer.id, layer.type);
            $canvas.append(buildElementLayerElement(layer));
            enableElementMovement(getCanvasLayer(layer.id, 'element'));
            syncLayerArrays();
            renderLayerList();
            selectElementLayer(layer.id);
        }

        function enableTextMovement($elements) {
            $elements.each(function() {
                var $note = $(this);
                $note.data('layer-move-ready', true);
            });
            enableScaledLayerDrag($elements, 'text');
        }

        function enableImageMovement($elements) {
            $elements.each(function() {
                var $overlay = $(this);
                $overlay.data('layer-move-ready', true);
                if ($overlay.data('ui-resizable')) {
                    $overlay.resizable('destroy');
                }
                $overlay.resizable({
                    handles: 'se',
                    start: function(event, ui) {
                        recordUndoSnapshot();
                    },
                    resize: function(event, ui) {
                        $('#imgid').val($(this).attr('id'));
                        $('#height').val(Math.round(ui.size.height));
                        $('#width').val(Math.round(ui.size.width));
                        $(this).data('base-width', Math.round(ui.size.width));
                        $(this).data('base-height', Math.round(ui.size.height));
                        $('#imageScale').val(100);
                        syncImageLayerPosition($(this));
                    },
                    stop: function(event, ui) {
                        syncImageLayerPosition($(this));
                    }
                });
            });
            enableScaledLayerDrag($elements, 'image');
        }

        function enableLayerMovement() {
            enableTextMovement($('.note'));
            enableImageMovement($('.overlay'));
            enableElementMovement($('.element-layer'));
        }

        function enableElementMovement($elements) {
            $elements.each(function() {
                var $element = $(this);
                $element.data('layer-move-ready', true);
                if ($element.data('ui-resizable')) {
                    $element.resizable('destroy');
                }
                $element.resizable({
                    handles: 'se',
                    start: function() {
                        recordUndoSnapshot();
                    },
                    resize: function(event, ui) {
                        $('#elementId').val($(this).attr('id'));
                        $('#elementWidth').val(Math.round(ui.size.width));
                        $('#elementHeight').val(Math.round(ui.size.height));
                        var layer = findLayerData($(this).attr('id'), 'element');
                        if (layer.id) {
                            layer.baseWidth = Math.round(ui.size.width);
                            layer.baseHeight = Math.round(ui.size.height);
                            layer.scale = 100;
                            $('#elementScale').val(100);
                        }
                        syncElementLayerPosition($(this));
                    },
                    stop: function() {
                        syncElementLayerPosition($(this));
                    }
                });
            });
            enableScaledLayerDrag($elements, 'element');
        }

    function get_items() {
        var template_id = $('#product-template_id').val();
        var language_id = $('#product-language_id').val();
        var csrfToken = $('meta[name="csrf-token"]').attr("content");

        var min = 10000;
        var max = 99999;
        var num = Math.floor(Math.random() * (max - min + 1)) + min;

        var id = num;
        var type = 'text';
        var itemName = $('#item').val();
        var itemName1 = itemName.trim();
        var posx = $('#posX').val();
        var posy = $('#posY').val();
        var textalign = $('#textalign').val();
        var color = $('#fontcolor').val();
        var fontstyle = $('#fontstyle').val();
        var fontweight = $('#fontweight').val();
        var fontsize = $('#fontsize').val();
        var fontfamily = $('#fontfamily').val();
        var width = $('#textwidth').val();
        var height = $('#textheight').val();
        var textrotate = $('#textrotate').val() || 0;


	        var Id = $('#textid').val();
	        var Id1 = String(Id || '');
            if (!Id1) {
                alert('Please select a text layer first');
                return;
            }
            applyTextFieldsToSelected();
            itemName = $('#item').val();
            itemName1 = itemName.trim();
            posx = $('#posX').val();
            posy = $('#posY').val();
            textalign = $('#textalign').val();
            color = $('#fontcolor').val();
            fontstyle = $('#fontstyle').val();
            fontweight = $('#fontweight').val();
            fontsize = $('#fontsize').val();
            fontfamily = $('#fontfamily').val();
            width = $('#textwidth').val();
            height = $('#textheight').val();
            textrotate = $('#textrotate').val() || 0;


        localStorage.uid = id;

	        var existingItem = updateList.find(function(item) {
	            return String(item.id) === Id1 && item.type === 'text';

        });

        if (Id1) {
            if (existingItem) {
                existingItem.x = posx;
                existingItem.y = posy;
                existingItem.text = itemName1;
                existingItem.textalign = textalign;
                existingItem.fontstyle = fontstyle;
                existingItem.color = color;
                existingItem.fontweight = fontweight;
                existingItem.fontsize = fontsize;
                existingItem.fontfamily = fontfamily;
                existingItem.width = width;
                existingItem.height = height;
                existingItem.rotate = textrotate;
                console.log("Item exists in updateList");
            } else {
                var newItem = {
                    id: Id1,
                    type: type,
                    x: posx,
                    y: posy,
                    text: itemName.trim(),
                    place: '',
                    color: color,
                    textalign: textalign,
                    fontstyle: fontstyle,
                    fontweight: fontweight,
                    fontsize: fontsize,
                    fontfamily: fontfamily,
                    width: width,
                    height: height,
                    rotate: textrotate,
                };


                console.log("Item does not exist in updateList");
                updateList.push(newItem);
                addLayerToOrder(newItem.id, newItem.type);
            }
        }
        console.log(updateList);
        console.log(newItem);
        syncLayerArrays();


        // populateTextBoxes(itemName);

        $.ajax({
            url: "<?= Url::toRoute(['product/updateitems']) ?>",
            method: "POST",
            data: {
                updateList: JSON.stringify(updateList),
                language_id: language_id,
                template_id: template_id,
                '_csrf-backend': csrfToken
            },
            success: function(result) {
                if (result == 0) {

                    var tname = $('input[name=item]').val();

                    var tid = $('input[name=textid]').val();
	                    if (tid !== '') {
	                        var $selectedTextLayer = getCanvasLayer(tid, 'text');
	                        $selectedTextLayer.text(tname);

                        var posx = $('#posX').val();
                        var posy = $('#posY').val();
                        $selectedTextLayer.css({
                            'left': posx + 'px',
	                            'top': posy + 'px'
	                        });
                            refreshLayerCard(tid, 'text');
                            selectTextLayer(tid);
                            syncLayerArrays();
	                    }

                    if (Id1) {
                        alert('save successful');
                    } else {
                        alert('please select a text');
                    }

	                } else {
                    alert('Error');
                }
            }

        });
    }
    
    $(document).ready(function() {
        var $item = null;
        var $currentDraggedItem = null;

        function drawPreviewRulers() {
            var img = document.getElementById('productPreviewLargeImg');
            if (!img || !img.naturalWidth) return;

            var baseWidth = img.naturalWidth;
            var baseHeight = img.naturalHeight;
            var renderedWidth = img.width;
            var renderedHeight = img.height;
            var previewScale = renderedWidth / baseWidth;

            var showRulers = $('#showCanvasRulers').is(':checked');
            var showGrid = $('#showCanvasGrid').is(':checked');

            // Update grid overlay dimensions and background size
            $('#previewGridOverlay')
                .toggle(showGrid)
                .css({
                    width: renderedWidth + 'px',
                    height: renderedHeight + 'px'
                });

            if (showGrid) {
                var gridSize = Math.max(6, Math.round(50 * previewScale));
                $('#previewGridOverlay').css({
                    'background-size': (gridSize * 2) + 'px ' + (gridSize * 2) + 'px, ' + (gridSize * 2) + 'px ' + (gridSize * 2) + 'px, ' + gridSize + 'px ' + gridSize + 'px, ' + gridSize + 'px ' + gridSize + 'px'
                });
            }

            // Rulers
            var tickEvery = 50;
            var majorEvery = 100;
            var topHtml = '';
            var leftHtml = '';

            for (var x = 0; x <= baseWidth; x += tickEvery) {
                var left = Math.round(x * previewScale);
                var isMajorX = x % majorEvery === 0;
                topHtml += '<span class="ruler-tick" style="left:' + left + 'px;height:' + (isMajorX ? 14 : 8) + 'px"></span>';
                if (isMajorX) {
                    topHtml += '<span class="ruler-label" style="left:' + (left + 3) + 'px;top:2px">' + x + '</span>';
                }
            }

            for (var y = 0; y <= baseHeight; y += tickEvery) {
                var top = Math.round(y * previewScale);
                var isMajorY = y % majorEvery === 0;
                leftHtml += '<span class="ruler-tick" style="top:' + top + 'px;width:' + (isMajorY ? 18 : 10) + 'px"></span>';
                if (isMajorY) {
                    leftHtml += '<span class="ruler-label" style="left:2px;top:' + (top + 2) + 'px">' + y + '</span>';
                }
            }

            $('#previewRulerTop')
                .toggle(showRulers)
                .css({
                    width: renderedWidth + 'px'
                })
                .html(topHtml);

            $('#previewRulerLeft')
                .toggle(showRulers)
                .css({
                    height: renderedHeight + 'px'
                })
                .html(leftHtml);
        }

        $('#productPreviewLargeImg').on('load', function() {
            drawPreviewRulers();
        });

        $(window).on('resize', function() {
            if ($('#productPreviewPopup').hasClass('is-open')) {
                drawPreviewRulers();
            }
        });

        $('#productPreviewThumb').on('click', function() {
            $('#productPreviewPopup').addClass('is-open').attr('aria-hidden', 'false');
            setTimeout(drawPreviewRulers, 50);
        });
        $('#productPreviewClose, #productPreviewPopup').on('click', function(event) {
            if (event.target !== this) {
                return;
            }
            $('#productPreviewPopup').removeClass('is-open').attr('aria-hidden', 'true');
        });
        $(document).on('keydown', function(event) {
            if (event.key === 'Escape') {
                $('#productPreviewPopup').removeClass('is-open').attr('aria-hidden', 'true');
                closeReusableLayerGallery();
                closeImageConvertPopup(false);
            }
        });
        $('#add').click(function() {
            recordUndoSnapshot();
            var template_id = $('#product-template_id').val();
            var csrfToken = $('meta[name="csrf-token"]').attr("content");
            $.ajax({
                url: "<?= Url::toRoute(['product/imagetype']) ?>",
                method: "POST",
                data: {
                    template_id: template_id,
                    '_csrf-backend': csrfToken
                },
                success: function(result) {
                    if (result) {
	                        var toAdd = $.trim($('input[name=item]').val()) || 'Enter Your Name';
	                        var uId = Date.now() + Math.floor(Math.random() * 1000);
	                        var posx = parseFloat($('#posX').val());
	                        var posy = parseFloat($('#posY').val());
	                        var fontSize = $('#fontsize').val() || 24;
	                        var textWidth = parseFloat($('#textwidth').val()) || 300;
	                        var textHeight = parseFloat($('#textheight').val()) || Math.ceil((parseFloat(fontSize) || 24) * 0.9);
	                        var textLayer = {
	                            id: uId,
	                            type: 'text',
	                            x: isNaN(posx) ? 0 : Math.round(posx),
	                            y: isNaN(posy) ? 0 : Math.round(posy),
	                            text: toAdd,
	                            place: '',
	                            color: $('#fontcolor').val() || '#FF0000',
	                            textalign: $('#textalign').val() || 'center',
	                            fontstyle: $('#fontstyle').val() || 'normal',
	                            fontweight: $('#fontweight').val() || 'normal',
	                            fontsize: fontSize,
	                            fontfamily: $('#fontfamily').val() || '',
	                            width: textWidth,
	                            height: textHeight,
	                            rotate: $('#textrotate').val() || 0,
	                            z_index: 0
	                        };

	                        localStorage.uid = uId;
	                        updateList.push(textLayer);
	                        addLayerToOrder(textLayer.id, textLayer.type);
	                        $item = buildTextLayerElement(textLayer);


                        $item.draggable({
                            cursor: "crosshair",
                            start: function(e, ui) {
                                // Set the currently dragged item
                                $currentDraggedItem = $(this);
                                rememberScaledDragStart($(this), e, ui);
                            },
                            stop: function(e, ui) {
                                applyScaledDragPosition($(this), e, ui);
                                $currentDraggedItem = null;
                            },
                            drag: function(e, ui) {
                                applyScaledDragPosition($(this), e, ui);


                                var template_id = $('#product-template_id').val();
                                var language_id = $('#product-language_id').val();
                                var text_id = $('#textid').val();
                                var csrfToken = $('meta[name="csrf-token"]').attr("content");
                                $.ajax({
                                    url: "<?= Url::toRoute(['product/autotextfetch']) ?>",
                                    method: "POST",
                                    data: {
                                        template_id: template_id,
                                        language_id: language_id,
                                        text_id: text_id,
                                        '_csrf-backend': csrfToken
                                    },
                                    success: function(result) {
                                        if (result != '') {
                                            var textDetails = JSON.parse(result);
                                            
                                            $.each(textDetails, function(i, item) {
                                                
                                                if (item.id == text_id) {
                                                    $('#fontsize').val(item.fontsize);
                                                    $('#textalign').val(item.textalign);
                                                    $('#fontstyle').val(item.fontstyle);
                                                    $('#fontcolor').val(normalizeHexColor(item.color));
                                                    $('#fontweight').val(item.fontweight);
                                                    $('#item').val(item.text);
                                                    $('#fontfamily').val(item.fontfamily);
                                                    $('#textwidth').val(item.width);
                                                    $('#textheight').val(item.height);
                                                    $('#textrotate').val(item.rotate || 0);

                                                }
                                            });
                                        }

                                    }
                                });

                                var itemName = $(this).text();
                                var textId = $(this).attr('id');

	                                var posx = Math.round(ui.position.left);
	                                var posy = Math.round(ui.position.top);
	                                selectTextLayer(textId);
	                                $('#posX').val(posx);
	                                $('#posY').val(posy);
	                            }
                        });

	                        if (result == 'square') {
	                            $item.appendTo('.xyz');
	                        } else {
	                            $item.appendTo('.xyz1');
	                        }
	                        textLayer.height = fitTextLayerHeight($item);
	                        $('#textid').val(uId);
	                        $('#posX').val(textLayer.x);
	                        $('#posY').val(textLayer.y);
	                        $('#textwidth').val(textLayer.width);
	                        $('#textheight').val(textLayer.height);
	                        enableTextMovement($item);
	                        syncLayerArrays();
	                        renderLayerList();
	                        applyLayerStack();
	                        selectTextLayer(uId);

                    } else {
                        alert('Error');
                    }
                }
            });
        });

	        $(document).on('click', '.layer-card', function() {
                var id = $(this).data('layer-id');
                var type = $(this).data('layer-type');

                if (type === 'background') {
                    selectBackgroundLayer();
                } else if (type === 'text') {
                    selectTextLayer(id);
                } else if (type === 'image') {
                    selectImageLayer(id);
                } else {
                    selectElementLayer(id);
                }
            });

            $(document).on('keydown', '.layer-card', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    $(this).trigger('click');
                }
            });

            $(document).on('click', '.layer-move-up, .layer-move-down', function(event) {
                event.preventDefault();
                event.stopPropagation();
                var $card = $(this).closest('.layer-card');
                var direction = $(this).hasClass('layer-move-up') ? 'up' : 'down';
                var id = $card.data('layer-id');
                var type = $card.data('layer-type');
                moveLayer(id, type, direction);
            });

            $(document).on('click', '.layer-bring-front, .layer-send-back', function(event) {
                event.preventDefault();
                event.stopPropagation();
                var $card = $(this).closest('.layer-card');
                var edge = $(this).hasClass('layer-bring-front') ? 'front' : 'back';
                moveLayerToEdge($card.data('layer-id'), $card.data('layer-type'), edge);
            });

            $(document).on('click', function(event) {
                var $target = $(event.target);
                if ($target.closest('#canvasViewport, .layer-card').length) {
                    return;
                }

                releaseTemporaryLayerPreview();
            });

            $('#deleteLayerBtn').on('click', deleteSelectedLayer);

            $('#canvasScale').on('input change', function() {
                applyCanvasScale(this.value);
            });
            $('#showCanvasGrid, #showCanvasRulers, #showCanvasGuides').on('change', function() {
                applyCanvasScale($('#canvasScale').val());
                drawPreviewRulers();

                if (!$('#showCanvasGuides').is(':checked')) {
                    hideGuideLines();
                } else if ($('#textid').val()) {
                    updateGuideLines(getCanvasLayer($('#textid').val(), 'text'));
                } else if ($('#imgid').val()) {
                    updateGuideLines(getCanvasLayer($('#imgid').val(), 'image'));
                } else if ($('#elementId').val()) {
                    updateGuideLines(getCanvasLayer($('#elementId').val(), 'element'));
                }
            });
            $(document).on('keydown', function(event) {
                if ((event.ctrlKey || event.metaKey) && String(event.key).toLowerCase() === 'z') {
                    if (undoLastEditorStep()) {
                        event.preventDefault();
                    }
                    return;
                }

                var tagName = String(event.target.tagName || '').toLowerCase();
                var isEditingField = tagName === 'input' || tagName === 'textarea' || tagName === 'select' || $(event.target).is('[contenteditable="true"]');
                var arrows = {
                    ArrowLeft: [-1, 0],
                    ArrowRight: [1, 0],
                    ArrowUp: [0, -1],
                    ArrowDown: [0, 1]
                };

                if (!arrows[event.key] || isEditingField) {
                    return;
                }

                var step = event.ctrlKey ? 1 : 10;
                var moved = nudgeSelectedLayer(arrows[event.key][0] * step, arrows[event.key][1] * step);

                if (moved) {
                    event.preventDefault();
                }
            });
            applyCanvasScale($('#canvasScale').val());

	        $(document).on('click', '.note', function() {
	            selectTextLayer($(this).attr('id'));
	        });
	        $(document).on('click', '.template-background-layer', function() {
	            selectBackgroundLayer();
	        });
	        $(document).on('click', '.element-layer', function() {
	            selectElementLayer($(this).attr('id'));
	        });
	        enableLayerMovement();
            updateList.forEach(function(layer) {
                applyLayerDataToElement(layer);
            });
            applyLayerStack();

        $('.note').on("drag", function(e, ui) {
            if ($currentDraggedItem) {
                var textWidth = $currentDraggedItem.outerWidth();
                var textheight = fitTextLayerHeight($currentDraggedItem);
                $('#textwidth').val(textWidth);
                $('#textheight').val(textheight);
            }
        });
        $('.note').on("mousedown", function() {
            $currentDraggedItem = $(this);
        });

        $(document).on("mousemove", function(e) {
            if ($currentDraggedItem) {
                var textWidth = $currentDraggedItem.outerWidth();
                var textheight = fitTextLayerHeight($currentDraggedItem);
                $('#textwidth').val(textWidth);
                $('#textheight').val(textheight);
            }
        });

	        $(document).on("mouseup", function() {
	            $currentDraggedItem = null;
	        });

            $('.product-form form').on('submit', function() {
                if ($('#textid').val()) {
                    applyTextFieldsToSelected();
                }
                if ($('#imgid').val()) {
                    applyImageFieldsToSelected();
                }
                if ($('#elementId').val()) {
                    applyElementFieldsToSelected();
                }
                syncLayerArrays();
            });

            $('#item, #posX, #posY, #textwidth, #textheight, #textrotate, #fontsize, #textalign, #fontstyle, #fontweight, #fontfamily, #fontcolor, #posLeft, #posTop, #height, #width, #rotate, #imageScale, #imageFilter, #imageOutline, #imageOutlineColor, #imagePlace, #elementX, #elementY, #elementWidth, #elementHeight, #elementRotation, #elementScale, #elementColor, #elementOpacity, #elementBorderColor, #elementBorderWidth')
                .on('focusin', function() {
                    if (!pendingFieldUndoSnapshot) {
                        recordUndoSnapshot();
                        pendingFieldUndoSnapshot = true;
                    }
                })
                .on('focusout', function() {
                    pendingFieldUndoSnapshot = false;
                });

            $('#textwidth').on('input change', function() {
                if ($('#textid').val()) {
                    applyTextFieldsToSelected();
                }
            });

            $('#elementX, #elementY, #elementWidth, #elementHeight, #elementRotation, #elementScale, #elementOpacity, #elementBorderWidth, #elementOffsetY, #elementPadding').on('input', applyElementFieldsToSelected);
            $('#elementColor, #elementBorderColor, #elementRelativeTo').on('change input', applyElementFieldsToSelected);
            $('#saveElementBtn').on('click', applyElementFieldsToSelected);

            $('.add-element').on('click', function() {
                addElementLayer($(this).data('element-type'));
            });
            $('#uploadLayerImageBtn').on('click', uploadLayerImage);
            $('#openReusableLayerGalleryBtn, #reuseImagePicker').on('click', function(event) {
                event.preventDefault();
                openReusableLayerGallery();
            });
            $('#reuseGalleryClose, #reuseGalleryPopup').on('click', function(event) {
                if (event.target !== this) {
                    return;
                }
                closeReusableLayerGallery();
            });
            $(document).on('click', '.reuse-gallery-item', function() {
                selectReusableLayerImage($(this).data('filename'));
                closeReusableLayerGallery();
            });
            $('#imageConvertWidth, #imageConvertHeight, #imageConvertQuality').on('input change', updateImageConvertPreview);
            $('#imageConvertUpload').on('click', uploadConvertedLayerImage);
            $('#imageConvertCancel, #imageConvertClose').on('click', function() {
                closeImageConvertPopup(true);
            });
            $('#imageConvertPopup').on('click', function(event) {
                if (event.target === this) {
                    closeImageConvertPopup(true);
                }
            });
            $('#addReusableLayerImageBtn').on('click', addReusableLayerImage);
            loadReusableLayerImages();

            renderLayerList();
            syncLayerArrays();
            var $firstLayer = $('.layer-card').first();
            if ($firstLayer.length) {
                $firstLayer.trigger('click');
            }
	    });




        function changeNoteColor(selectedColor) {
            $('#fontcolor').val(normalizeHexColor(selectedColor));
            applyTextFieldsToSelected();
	    }

    function changeNoteFontstyle(selectedFontstyle) {
	        $('#fontstyle').val(selectedFontstyle);
            applyTextFieldsToSelected();
	    }

    function changeNoteFontweight(selectedFontweight) {
	        $('#fontweight').val(selectedFontweight);
            applyTextFieldsToSelected();
	    }

    function changeNoteTextalign(selectedTextalign) {
	        $('#textalign').val(selectedTextalign);
            applyTextFieldsToSelected();
	    }

    function changeNoteFontsize(selectedFontsize) {
	        $('#fontsize').val(selectedFontsize);
            applyTextFieldsToSelected();
	    }

    function stepFontSize(delta) {
            var currentSize = parseInt($('#fontsize').val(), 10) || 16;
            var nextSize = Math.max(8, Math.min(200, currentSize + delta));
            $('#fontsize').val(nextSize);
            changeNoteFontsize(nextSize);
        }

    function changeNoteFontfamily(selectedFontfamily) {
	        $('#fontfamily').val(selectedFontfamily);
            applyTextFieldsToSelected();
	    }

    function refreshFormatToggles() {
            $('#fontBoldToggle').toggleClass('is-active', $('#fontweight').val() === 'bold');
            $('#fontItalicToggle').toggleClass('is-active', $('#fontstyle').val() === 'italic');
        }

    function toggleBoldFormat() {
            var nextWeight = $('#fontweight').val() === 'bold' ? 'normal' : 'bold';
            $('#fontweight').val(nextWeight);
            changeNoteFontweight(nextWeight);
            refreshFormatToggles();
        }

    function toggleItalicFormat() {
            var nextStyle = $('#fontstyle').val() === 'italic' ? 'normal' : 'italic';
            $('#fontstyle').val(nextStyle);
            changeNoteFontstyle(nextStyle);
            refreshFormatToggles();
        }
        $('#fontcolor').val(normalizeHexColor($('#fontcolor').val()));
        refreshFormatToggles();
        $('#fontBoldToggle').on('click', toggleBoldFormat);
        $('#fontItalicToggle').on('click', toggleItalicFormat);
        $('#increaseFontSize').on('click', function() {
            stepFontSize(1);
        });
        $('#decreaseFontSize').on('click', function() {
            stepFontSize(-1);
        });
        $('#item, #posX, #posY, #textwidth, #textheight, #textrotate, #fontsize').on('input', applyTextFieldsToSelected);
        $('#textalign, #fontstyle, #fontweight, #fontfamily, #fontcolor').on('change input', applyTextFieldsToSelected);
	</script>

<!-- image start -->

<script>
    var existingImage = <?= Json::htmlEncode((string) $model->item_name) ?>;
    // var updateImage = JSON.parse(existingList);
    var updateImage = existingImage ? JSON.parse(existingImage) : [];


    var appendedImage = [];

    console.log(updateImage);

    function get_img() {
        var template_id = $('#product-template_id').val();
        var language_id = $('#product-language_id').val();
        var csrfToken = $('meta[name="csrf-token"]').attr("content");



        var min = 10000;
        var max = 99999;
        var num = Math.floor(Math.random() * (max - min + 1)) + min;

        var id = num;
        var type = 'image';
        var posLeft = $('#posLeft').val();
        var posTop = $('#posTop').val();
        var itemName = 'image';
        var imagePlace = $('#imagePlace').val();
        var height = $('#height').val();
        var width = $('#width').val();
        var rotate = $('#rotate').val();
        var scale = $('#imageScale').val() || 100;
        var imageFilter = getImageFilterValue();
        var outlineWidth = getImageOutlineValue();
        var outlineColor = outlineWidth ? getImageOutlineColorValue() : '#ffffff';

	        var Id = $('#imgid').val();
	        var Id1 = parseInt(Id);
            if (!Id1) {
                alert('Please select an image layer first');
                return;
            }
            applyImageFieldsToSelected();
            posLeft = $('#posLeft').val();
            posTop = $('#posTop').val();
            imagePlace = $('#imagePlace').val();
            height = $('#height').val();
            width = $('#width').val();
            rotate = $('#rotate').val();
            scale = $('#imageScale').val() || 100;
            imageFilter = getImageFilterValue();
            outlineWidth = getImageOutlineValue();
            outlineColor = outlineWidth ? getImageOutlineColorValue() : '#ffffff';



        localStorage.uid = id;


	        var existingImage = updateImage.find(function(image) {
	            return parseInt(image.id, 10) === Id1 && image.type === 'image';
        });

        if (Id1) {

            if (existingImage) {
                existingImage.x = posLeft;
                existingImage.y = posTop;
                existingImage.place = imagePlace;
                existingImage.height = height;
                existingImage.width = width;
                existingImage.rotate = rotate;
                existingImage.scale = scale;
                existingImage.image_filter = imageFilter;
                existingImage.outline_width = outlineWidth;
                existingImage.outline_color = outlineColor;
                existingImage.baseWidth = existingImage.baseWidth || width;
                existingImage.baseHeight = existingImage.baseHeight || height;
                console.log("Item  exist in updateList");
            } else {
                var newImage = {
                    id: Id1,
                    type: type,
                    x: posLeft,
                    y: posTop,
                    text: itemName,
                    place: imagePlace,
                    height: height,
                    width: width,
                    rotate: rotate,
                    scale: scale,
                    image_filter: imageFilter,
                    outline_width: outlineWidth,
                    outline_color: outlineColor,
                    baseWidth: width,
                    baseHeight: height,


                };
                console.log("Item does not exist in updateList");
                updateImage.push(newImage);
                addLayerToOrder(newImage);


            }
        }
        console.log(newImage);
        console.log(updateImage);
        updateList = updateImage.slice();
        syncLayerArrays();


        $.ajax({
            url: "<?= Url::toRoute(['product/updateimageplace']) ?>",
            method: "POST",
            data: {
                updateImage: JSON.stringify(updateImage),
                language_id: language_id,
                template_id: template_id,
                '_csrf-backend': csrfToken
            },
            success: function(result) {
                if (result == 0) {

	                    var tname = $('input[name=imagePlace]').val();
	                    var tid = $('input[name=imgid]').val();
	                    if (tid !== '') {
	                        getCanvasLayer(tid, 'image').css({
                                left: $('#posLeft').val() + 'px',
                                top: $('#posTop').val() + 'px',
                                width: $('#width').val() + 'px',
                                height: $('#height').val() + 'px',
                                transform: 'rotate(' + ($('#rotate').val() || 0) + 'deg)'
                            });
                            refreshLayerCard(tid, 'image');
                            selectImageLayer(tid);

	                    }
                    if (tid) {
                        alert('Image Place Updated Successfully');
                    } else {
                        alert('please select a image');
                    }

	                } else {
                    alert('Error');
                }
            }
        });
    }






    $(document).ready(function() {
        $('.overlay').draggable({

            cursor: "crosshair",
            start: function(e, ui) {
                rememberScaledDragStart($(this), e, ui);
            },
            drag: function(e, ui) {
                applyScaledDragPosition($(this), e, ui);


                var template_id = $('#product-template_id').val();
                var language_id = $('#product-language_id').val();
                var img_id = $('#imgid').val();
                var csrfToken = $('meta[name="csrf-token"]').attr("content");
                $.ajax({
                    url: "<?= Url::toRoute(['product/autofetchimage']) ?>",
                    method: "POST",
                    data: {
                        template_id: template_id,
                        language_id: language_id,
                        img_id: img_id,
                        '_csrf-backend': csrfToken
                    },
                    success: function(result) {
                        if (result != '') {
                            var imgDetails = JSON.parse(result);
                            console.log(imgDetails);
                            $.each(imgDetails, function(i, item) {
                                if (item.id == img_id) {
                                    $('#height').val(item.height);
                                    $('#width').val(item.width);
                                    $('#rotate').val(item.rotate);
                                    $('#imagePlace').val(item.place);
                                }
                            });
                        }

                    }
                });

                var imgId = $(this).attr('id');
                var posLeft = Math.floor(ui.position.left);
                var posTop = Math.floor(ui.position.top);
                var selectedval = $('#imagePlace').val();
                var imagePlace = selectedval;


                var overlay = $(this);
                overlay.resizable({
                    handles: 'se',
                    resize: function(event, ui) {
                        var width = ui.size.width;
                        var height = ui.size.height;

                        overlay.css({
                            'width': width + 'px',
                            'height': height + 'px'
                        });
                        $('#height').val(height);
                        $('#width').val(width);
                    }
                })



	                selectImageLayer(imgId);
	                $('#posLeft').val(posLeft);
	                $('#posTop').val(posTop);
	                $('#imagePlace').val(selectedval);

            }
        });
        var $itemImg = null;

        $('#addImg').click(function() {
            recordUndoSnapshot();
            var template_id = $('#product-template_id').val();
            var csrfToken = $('meta[name="csrf-token"]').attr("content");
            var posLeft = parseInt($('#posLeft').val(), 10) || 0;
            var posTop = parseInt($('#posTop').val(), 10) || 0;
            var layerWidth = parseInt($('#width').val(), 10) || 100;
            var layerHeight = parseInt($('#height').val(), 10) || 100;
            var layerRotate = parseInt($('#rotate').val(), 10) || 0;
            var layerPlace = $('#imagePlace').val() || 'fg';
            var layerScale = parseInt($('#imageScale').val(), 10) || 100;
            var layerFilter = getImageFilterValue();
            var layerOutline = String(layerPlace).toLowerCase() === 'fg' ? (parseInt($('#imageOutline').val(), 10) || 0) : 0;
            var layerOutlineColor = layerOutline ? getImageOutlineColorValue() : '#ffffff';
            var baseLayerWidth = layerWidth;
            var baseLayerHeight = layerHeight;
            layerWidth = Math.max(1, Math.round(baseLayerWidth * layerScale / 100));
            layerHeight = Math.max(1, Math.round(baseLayerHeight * layerScale / 100));
            $.ajax({
                url: "<?= Url::toRoute(['product/imagetype']) ?>",
                method: "POST",
                data: {
                    template_id: template_id,
                    '_csrf-backend': csrfToken
                },
                success: function(result) {
                    if (result) {
	                        $itemImg = $('<div class="overlay"/>');
	                        var uId = Date.now() + Math.floor(Math.random() * 1000);
	                        $itemImg.attr('id', uId);
	                        $itemImg.attr('data-layer-type', 'image');
	                        $itemImg.attr('data-base-width', baseLayerWidth);
	                        $itemImg.attr('data-base-height', baseLayerHeight);

                        $itemImg.css({
                            'position': 'absolute',
                            'left': posLeft + 'px',
                            'top': posTop + 'px',
                            'width': layerWidth + 'px',
                            'height': layerHeight + 'px',
                            'transform': 'rotate(' + layerRotate + 'deg)',
                            'filter': getImageCssFilter(layerFilter),
                            'background-color': 'rgba(0, 0, 0, 0.5)',
                            'border': '2px solid ' + (layerPlace === 'fg' ? 'red' : 'white'),
                        });

                        $itemImg.draggable({
                            cursor: "crosshair",
                            start: function(e, ui) {
                                rememberScaledDragStart($(this), e, ui);
                            },
                            drag: function(e, ui) {
                                applyScaledDragPosition($(this), e, ui);


                                var template_id = $('#product-template_id').val();
                                var language_id = $('#product-language_id').val();
                                var img_id = $('#imgid').val();
                                var csrfToken = $('meta[name="csrf-token"]').attr("content");
                                $.ajax({
                                    url: "<?= Url::toRoute(['product/autofetchimage']) ?>",
                                    method: "POST",
                                    data: {
                                        template_id: template_id,
                                        language_id: language_id,
                                        img_id: img_id,
                                        '_csrf-backend': csrfToken
                                    },
                                    success: function(result) {
                                        if (result != '') {
                                            var imgDetails = JSON.parse(result);
                                            console.log(imgDetails);
                                            $.each(imgDetails, function(i, item) {
                                                
                                                if (item.id == img_id) {
                                                    $('#height').val(item.height);
                                                    $('#width').val(item.width);
                                                    $('#rotate').val(item.rotate);
                                                    $('#imagePlace').val(item.place);
                                                }
                                            });
                                        }

                                    }
                                });

                                var imgId = $(this).attr('id');

                                var posLeft = Math.floor(ui.position.left);
                                var posTop = Math.floor(ui.position.top);
                                var selectedval = $('#imagePlace').val();

                                var overlay = $(this);

                                overlay.resizable({
                                    handles: 'se',
                                    resize: function(event, ui) {

                                        var width = ui.size.width;
                                        var height = ui.size.height;

                                        overlay.css({
                                            'width': width + 'px',
                                            'height': height + 'px'
                                        });

                                        $('#height').val(height);
                                        $('#width').val(width);

                                    }
                                })

	                                selectImageLayer(imgId);
	                                $('#posLeft').val(posLeft);
	                                $('#posTop').val(posTop);
	                                $('#imagePlace').val(selectedval);


                            }
                        });



	                        if (result == 'square') {
	                            $('.xyz').append($itemImg);
	                        } else {
	                            $('.xyz1').append($itemImg);
	                        }
                            $('#imgid').val(uId);
                            $('#imagePlace').val(layerPlace);
                            $('#posLeft').val(posLeft);
                            $('#posTop').val(posTop);
                            $('#height').val(layerHeight);
                            $('#width').val(layerWidth);
                            $('#rotate').val(layerRotate);
                            $('#imageScale').val(layerScale);
                            $('#imageFilter').val(layerFilter);
                            $('#imageOutline').val(layerOutline);
                            $('#imageOutlineColor').val(layerOutlineColor);
                            toggleImageOutlineControl();

                            var newImageLayer = {
                                id: uId,
                                type: 'image',
                                x: posLeft,
                                y: posTop,
                                text: 'image',
                                place: layerPlace,
                                height: layerHeight,
                                width: layerWidth,
                                rotate: layerRotate,
                                scale: layerScale,
                                image_filter: layerFilter,
                                outline_width: layerOutline,
                                outline_color: layerOutlineColor,
                                baseWidth: baseLayerWidth,
                                baseHeight: baseLayerHeight
                            };

                            updateList.push(newImageLayer);
                            updateImage.push(newImageLayer);
                            addLayerToOrder(newImageLayer);
                            syncLayerArrays();
                            renderLayerList();

	                        enableImageMovement($itemImg);
	                        selectImageLayer(uId);

                    } else {
                        alert('Error');
                    }
                }
            });
        });
	        $(document).on('click', '.overlay', function() {
	            selectImageLayer($(this).attr('id'));
	        });

            enableLayerMovement();
	    });



	    function rotateOverlay(rotationAngle) {
	        $('#rotate').val(rotationAngle);
            applyImageFieldsToSelected();
	    }

        $('#posLeft, #posTop, #height, #width, #rotate, #imageScale, #imageFilter, #imageOutline, #imageOutlineColor, #imageRelativeTo, #imageOffsetY').on('input change', applyImageFieldsToSelected);
        $('#imagePlace').on('change', function() {
            toggleImageOutlineControl();
            applyImageFieldsToSelected();
        });
        toggleImageOutlineControl();
</script>
