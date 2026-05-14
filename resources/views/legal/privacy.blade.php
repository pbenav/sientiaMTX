<x-legal-layout title="Política de Privacidad">
@if($content)
    {!! $content !!}
@else

<h1>Política de Privacidad de {{ config('app.name', 'Sientia') }}</h1>
<p class="meta">Última actualización: <strong>{{ date('d/m/Y') }}</strong></p>

<p>En <strong>{{ config('app.name', 'Sientia') }}</strong>, nos comprometemos a proteger su privacidad. Esta Política explica cómo recopilamos, usamos y protegemos sus datos personales de acuerdo con el RGPD.</p>

<h2>1. Responsable del Tratamiento</h2>
<ul>
    <li><strong>Nombre:</strong> <span class="field">[Nombre o razón social]</span></li>
    <li><strong>NIF/CIF:</strong> <span class="field">[NIF/CIF]</span></li>
    <li><strong>Dirección:</strong> <span class="field">[Dirección postal completa]</span></li>
    <li><strong>Correo electrónico:</strong> <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a></li>
</ul>

<h2>2. Datos que Recopilamos</h2>
<p>Recopilamos datos de cuenta (nombre, correo), datos de uso (IP, navegador) y datos de integraciones (Google, etc.) que usted decida habilitar.</p>

<h2>3. Finalidades y Base Legal</h2>
<p>Tratamos sus datos para prestar el Servicio, mejorar nuestra plataforma y cumplir con obligaciones legales. La base jurídica principal es la ejecución del contrato y el interés legítimo para la seguridad.</p>

<h2>4. Conservación</h2>
<p>Los datos se conservarán mientras dure la relación contractual y durante los plazos legales posteriores.</p>

<h2>5. Sus Derechos</h2>
<p>Usted tiene derecho a acceder, rectificar, suprimir y oponerse al tratamiento de sus datos. Puede ejercer estos derechos escribiendo a <strong>{{ config('mail.from.address') }}</strong>. También puede reclamar ante la autoridad de control (AEPD en España).</p>

<h2>6. Seguridad</h2>
<p>Implementamos medidas técnicas avanzadas para proteger sus datos contra accesos no autorizados.</p>

<h2>7. Contacto</h2>
<p>
    <strong><span class="field">[Nombre de la Empresa]</span></strong><br>
    <strong><span class="field">[Dirección física]</span></strong><br>
    <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
</p>


@endif
</x-legal-layout>
