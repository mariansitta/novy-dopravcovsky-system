<html>
<head>
    <style type="text/css">
        body {
            font-family: "Poppins", sans-serif;
            color: #000;
            font-size: 13px;
            line-height: 1.5;
        }
        .transport-link {
            font-size: 1rem;
            color: #FF0000;
        }
        .notice {
            margin: 10px 0 14px 0;
        }
    </style>
</head>
<body>

    {{ trans('texts.email.notify-text-1') }} {{ $order_number }} {{ trans('texts.email.notify-text-2') }}
    <br />

    @if(!empty($notice))
        <div class="notice">
            {!! nl2br(e($notice)) !!}
        </div>
    @endif

    <a class="transport-link" target="_blank" href="{{ $link }}">{{ trans('texts.email.click-here') }}</a>
    <br />

    --
    <br><br>
    Damaro Slovakia s.r.o.
    <br>
    Chotarna 1
    <br>
    94901 Nitra
    <br>
    Email: <a href="mailto:orders@damaro-slovakia.eu">orders@damaro-slovakia.eu</a>
    <br><br>
    <i>
        Táto správa je určená výlučne pre uvedeného príjemcu a môže obsahovať dôverné, či chránené údaje. Ak ste obdržali tento email omylom, prosíme obratom o tom informujte odosielateľa a správu vymažte, vrátane jej príloh. Akékoľvek neoprávnené použitie alebo šírenie informácií obsiahnutých v tomto maili je zakázané.
        <br>
        Ďakujeme za pochopenie.
        <br>
        This message is intended only for the named recipient and may contain confidential or secure information. If you have received this email in error, please advise sender and delete this email with all attachments. Any unauthorized use of this email or any information, contained in this mail, is fully prohibited.
        <br>
        Thank you for your understanding.
        <br>
        Prevádzkovateľ Damaro Slovakia, s.r.o. spracováva osobné údaje v súlade s Nariadením EU č. <b>2016/679 o ochrane osobných údajov</b> (GDPR) a ďalšou legislatívou. Viac informácií na našich webových stránkach: <a href="http://damaro-slovakia.eu/sk/gdpr">http://damaro-slovakia.eu/sk/gdpr</a>
    </i>

</body>
</html>
