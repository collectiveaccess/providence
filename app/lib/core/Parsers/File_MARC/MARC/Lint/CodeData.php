<?php

/* vim: set expandtab shiftwidth=4 tabstop=4 softtabstop=4 foldmethod=marker: */

/**
 * Code Data to support Lint for MARC records
 *
 * This module is adapted from the MARC::Lint::CodeData CPAN module for Perl,
 * maintained by Bryan Baldus <eijabb@cpan.org> and available for download at
 * http://search.cpan.org/~eijabb/
 *
 * Current MARC::Lint::CodeData version used as basis for this module: 1.28
 *
 * PHP version 5
 *
 * LICENSE: This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category  File_Formats
 * @package   File_MARC
 * @author    Demian Katz <demian.katz@villanova.edu>
 * @author    Dan Scott <dscott@laurentian.ca>
 * @copyright 2003-2008 Oy Realnode Ab, Dan Scott
 * @license   http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version   CVS: $Id: Record.php 308146 2011-02-08 20:36:20Z dbs $
 * @link      http://pear.php.net/package/File_MARC
 */

// {{{ class File_MARC_Lint
/**
 * Contains codes from the MARC code lists for Geographic Areas, Languages, and
 * Countries.
 *
 * Code data is used for validating fields 008, 040, 041, and 043.
 *
 * Also, sources for subfield 2 in 600-651 and 655.
 *
 * Note: According to the official MARC documentation, Sears is not a valid 655
 * term. The code data below treats it as valid, in anticipation of a change in
 * the official documentation.
 *
 * @category File_Formats
 * @package  File_MARC
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Dan Scott <dscott@laurentian.ca>
 * @license  http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @link     http://pear.php.net/package/File_MARC
 */
class File_MARC_Lint_CodeData
{

    // {{{ properties
    /**
     * Valid Geographic Area Codes
     * @var array
     */
    public $geogAreaCodes;

    /**
     * Obsolete Geographic Area Codes
     * @var array
     */
    public $obsoleteGeogAreaCodes;

    /**
     * Valid Language Codes
     * @var array
     */
    public $languageCodes;

    /**
     * Obsolete Language Codes
     * @var array
     */
    public $obsoleteLanguageCodes;

    /**
     * Valid Country Codes
     * @var array
     */
    public $countryCodes;

    /**
     * Obsolete Country Codes
     * @var array
     */
    public $obsoleteCountryCodes;

    /**
     * Valid sources for fields 600-651
     * @var array
     */
    public $sources600_651;

    /**
     * Obsolete sources for fields 600-651
     * @var array
     */
    public $obsoleteSources600_651;

    /**
     * Valid sources for field 655
     * @var array
     */
    public $sources655;

    /**
     * Obsolete sources for field 655
     * @var array
     */
    public $obsoleteSources655;

    // }}}

    // {{{ Constructor: function __construct()
    /**
     * Start function
     *
     * Initialize code arrays.
     *
     * @return true 
     */
    public function __construct()
    {
        // @codingStandardsIgnoreStart
        // fill the valid Geographic Area Codes array
        $this->geogAreaCodes = explode("\t", "a-af---	f------	fc-----	fe-----	fq-----	ff-----	fh-----	fs-----	fb-----	fw-----	n-us-al	n-us-ak	e-aa---	n-cn-ab	f-ae---	ea-----	sa-----	poas---	aa-----	sn-----	e-an---	f-ao---	nwxa---	a-cc-an	t------	nwaq---	nwla---	n-usa--	ma-----	ar-----	au-----	r------	s-ag---	n-us-az	n-us-ar	a-ai---	nwaw---	lsai---	u-ac---	a------	ac-----	as-----	l------	fa-----	u------	u-at---	u-at-ac	e-au---	a-aj---	lnaz---	nwbf---	a-ba---	ed-----	eb-----	a-bg---	nwbb---	a-cc-pe	e-bw---	e-be---	ncbh---	el-----	ab-----	f-dm---	lnbm---	a-bt---	mb-----	a-ccp--	s-bo---	nwbn---	a-bn---	e-bn---	f-bs---	lsbv---	s-bl---	n-cn-bc	i-bi---	nwvb---	a-bx---	e-bu---	f-uv---	a-br---	f-bd---	n-us-ca	a-cb---	f-cm---	n-cn---	nccz---	lnca---	lncv---	cc-----	poci---	ak-----	e-urk--	e-urr--	nwcj---	f-cx---	nc-----	e-urc--	f-cd---	s-cl---	a-cc---	a-cc-cq	i-xa---	i-xb---	q------	s-ck---	n-us-co	b------	i-cq---	f-cf---	f-cg---	fg-----	n-us-ct	pocw---	u-cs---	nccr---	e-ci---	nwcu---	nwco---	a-cy---	e-xr---	e-cs---	f-iv---	eo-----	zd-----	n-us-de	e-dk---	dd-----	d------	f-ft---	nwdq---	nwdr---	x------	n-usr--	ae-----	an-----	a-em---	poea---	xa-----	s-ec---	f-ua---	nces---	e-uk-en	f-eg---	f-ea---	e-er---	f-et---	me-----	e------	ec-----	ee-----	en-----	es-----	ew-----	lsfk---	lnfa---	pofj---	e-fi---	n-us-fl	e-fr---	h------	s-fg---	pofp---	a-cc-fu	f-go---	pogg---	f-gm---	a-cc-ka	awgz---	n-us-ga	a-gs---	e-gx---	e-ge---	e-gw---	f-gh---	e-gi---	e-uk---	e-uk-ui	nl-----	np-----	fr-----	e-gr---	n-gl---	nwgd---	nwgp---	pogu---	a-cc-kn	a-cc-kc	ncgt---	f-gv---	f-pg---	a-cc-kw	s-gy---	a-cc-ha	nwht---	n-us-hi	i-hm---	a-cc-hp	a-cc-he	a-cc-ho	ah-----	nwhi---	ncho---	a-cc-hk	a-cc-hh	n-cnh--	a-cc-hu	e-hu---	e-ic---	n-us-id	n-us-il	a-ii---	i------	n-us-in	ai-----	a-io---	a-cc-im	m------	c------	n-us-ia	a-ir---	a-iq---	e-ie---	a-is---	e-it---	nwjm---	lnjn---	a-ja---	a-cc-ku	a-cc-ki	a-cc-kr	poji---	a-jo---	zju----	n-us-ks	a-kz---	n-us-ky	f-ke---	poki---	pokb---	a-kr---	a-kn---	a-ko---	a-cck--	a-ku---	a-kg---	a-ls---	cl-----	e-lv---	a-le---	nwli---	f-lo---	a-cc-lp	f-lb---	f-ly---	e-lh---	poln---	e-li---	n-us-la	e-lu---	a-cc-mh	e-xn---	f-mg---	lnma---	n-us-me	f-mw---	am-----	a-my---	i-xc---	f-ml---	e-mm---	n-cn-mb	poxd---	n-cnm--	zma----	poxe---	nwmq---	n-us-md	n-us-ma	f-mu---	i-mf---	i-my---	mm-----	ag-----	pome---	zme----	n-mx---	nm-----	n-us-mi	pott---	pomi---	n-usl--	aw-----	n-usc--	poxf---	n-us-mn	n-us-ms	n-usm--	n-us-mo	n-uss--	e-mv---	e-mc---	a-mp---	n-us-mt	nwmj---	zmo----	f-mr---	f-mz---	f-sx---	ponu---	n-us-nb	a-np---	zne----	e-ne---	nwna---	n-us-nv	n-cn-nk	ponl---	n-usn--	a-nw---	n-us-nh	n-us-nj	n-us-nm	u-at-ne	n-us-ny	u-nz---	n-cn-nf	ncnq---	f-ng---	fi-----	f-nr---	fl-----	a-cc-nn	poxh---	n------	ln-----	n-us-nc	n-us-nd	pn-----	n-use--	xb-----	e-uk-ni	u-at-no	n-cn-nt	e-no---	n-cn-ns	n-cn-nu	po-----	n-us-oh	n-uso--	n-us-ok	a-mk---	n-cn-on	n-us-or	zo-----	p------	a-pk---	popl---	ncpn---	a-pp---	aopf---	s-py---	n-us-pa	ap-----	s-pe---	a-ph---	popc---	zpl----	e-pl---	pops---	e-po---	n-cnp--	n-cn-pi	nwpr---	ep-----	a-qa---	a-cc-ts	u-at-qn	n-cn-qu	mr-----	er-----	n-us-ri	sp-----	nr-----	e-rm---	e-ru---	e-ur---	e-urf--	f-rw---	i-re---	nwsd---	fd-----	nweu---	lsxj---	nwxi---	nwxk---	nwst---	n-xl---	nwxm---	pows---	posh---	e-sm---	f-sf---	n-cn-sn	zsa----	a-su---	ev-----	e-uk-st	f-sg---	i-se---	a-cc-ss	a-cc-sp	a-cc-sm	a-cc-sh	e-urs--	e-ure--	e-urw--	a-cc-sz	f-sl---	a-si---	e-xo---	e-xv---	i-xo---	zs-----	pobp---	f-so---	f-sa---	s------	az-----	ls-----	u-at-sa	n-us-sc	ao-----	n-us-sd	lsxs---	ps-----	xc-----	n-usu--	n-ust--	e-urn--	e-sp---	f-sh---	aoxp---	a-ce---	f-sj---	fn-----	fu-----	zsu----	s-sr---	lnsb---	nwsv---	f-sq---	e-sw---	e-sz---	a-sy---	a-ch---	a-ta---	f-tz---	u-at-tm	n-us-tn	i-fs---	n-us-tx	a-th---	af-----	a-cc-tn	a-cc-ti	at-----	f-tg---	potl---	poto---	nwtr---	lstd---	w------	f-ti---	a-tu---	a-tk---	nwtc---	potv---	f-ug---	e-un---	a-ts---	n-us---	nwuc---	poup---	e-uru--	zur----	s-uy---	n-us-ut	a-uz---	ponn---	e-vc---	s-ve---	zve----	n-us-vt	u-at-vi	a-vt---	nwvi---	n-us-va	e-urp--	fv-----	powk---	e-uk-wl	powf---	n-us-dc	n-us-wa	n-usp--	awba---	nw-----	n-us-wv	u-at-we	xd-----	f-ss---	nwwi---	n-us-wi	n-us-wy	a-ccs--	a-cc-su	a-ccg--	a-ccy--	ay-----	a-ye---	e-yu---	n-cn-yk	a-cc-yu	fz-----	f-za---	a-cc-ch	f-rh---	u-atc--	u-ate--	u-atn--	e-rb---	e-mo---	e-kv---");
        
        // fill the obsolete Geographic Area Codes array
        $this->obsoleteGeogAreaCodes = explode("\t", "t-ay---	e-ur-ai	e-ur-aj	nwbc---	e-ur-bw	f-by---	pocp---	e-url--	cr-----	v------	e-ur-er	et-----	e-ur-gs	pogn---	nwga---	nwgs---	a-hk---	ei-----	f-if---	awiy---	awiw---	awiu---	e-ur-kz	e-ur-kg	e-ur-lv	e-ur-li	a-mh---	cm-----	e-ur-mv	n-usw--	a-ok---	a-pt---	e-ur-ru	pory---	nwsb---	posc---	a-sk---	posn---	e-uro--	e-ur-ta	e-ur-tk	e-ur-un	e-ur-uz	a-vn---	a-vs---	nwvr---	e-urv--	a-ys---");
        
        // fill the valid Language Codes array
        $this->languageCodes = explode("\t", "   	aar	abk	ace	ach	ada	ady	afa	afh	afr	ain	aka	akk	alb	ale	alg	alt	amh	ang	anp	apa	ara	arc	arg	arm	arn	arp	art	arw	asm	ast	ath	aus	ava	ave	awa	aym	aze	bad	bai	bak	bal	bam	ban	baq	bas	bat	bej	bel	bem	ben	ber	bho	bih	bik	bin	bis	bla	bnt	bos	bra	bre	btk	bua	bug	bul	bur	byn	cad	cai	car	cat	cau	ceb	cel	cha	chb	che	chg	chi	chk	chm	chn	cho	chp	chr	chu	chv	chy	cmc	cop	cor	cos	cpe	cpf	cpp	cre	crh	crp	csb	cus	cze	dak	dan	dar	day	del	den	dgr	din	div	doi	dra	dsb	dua	dum	dut	dyu	dzo	efi	egy	eka	elx	eng	enm	epo	est	ewe	ewo	fan	fao	fat	fij	fil	fin	fiu	fon	fre	frm	fro	frr	frs	fry	ful	fur	gaa	gay	gba	gem	geo	ger	gez	gil	gla	gle	glg	glv	gmh	goh	gon	gor	got	grb	grc	gre	grn	gsw	guj	gwi	hai	hat	hau	haw	heb	her	hil	him	hin	hit	hmn	hmo	hrv	hsb	hun	hup	iba	ibo	ice	ido	iii	ijo	iku	ile	ilo	ina	inc	ind	ine	inh	ipk	ira	iro	ita	jav	jbo	jpn	jpr	jrb	kaa	kab	kac	kal	kam	kan	kar	kas	kau	kaw	kaz	kbd	kha	khi	khm	kho	kik	kin	kir	kmb	kok	kom	kon	kor	kos	kpe	krc	krl	kro	kru	kua	kum	kur	kut	lad	lah	lam	lao	lat	lav	lez	lim	lin	lit	lol	loz	ltz	lua	lub	lug	lui	lun	luo	lus	mac	mad	mag	mah	mai	mak	mal	man	mao	map	mar	mas	may	mdf	mdr	men	mga	mic	min	mis	mkh	mlg	mlt	mnc	mni	mno	moh	mon	mos	mul	mun	mus	mwl	mwr	myn	myv	nah	nai	nap	nau	nav	nbl	nde	ndo	nds	nep	new	nia	nic	niu	nno	nob	nog	non	nor	nqo	nso	nub	nwc	nya	nym	nyn	nyo	nzi	oci	oji	ori	orm	osa	oss	ota	oto	paa	pag	pal	pam	pan	pap	pau	peo	per	phi	phn	pli	pol	pon	por	pra	pro	pus	que	raj	rap	rar	roa	roh	rom	rum	run	rup	rus	sad	sag	sah	sai	sal	sam	san	sas	sat	scn	sco	sel	sem	sga	sgn	shn	sid	sin	sio	sit	sla	slo	slv	sma	sme	smi	smj	smn	smo	sms	sna	snd	snk	sog	som	son	sot	spa	srd	srn	srp	srr	ssa	ssw	suk	sun	sus	sux	swa	swe	syc	syr	tah	tai	tam	tat	tel	tem	ter	tet	tgk	tgl	tha	tib	tig	tir	tiv	tkl	tlh	tli	tmh	tog	ton	tpi	tsi	tsn	tso	tuk	tum	tup	tur	tut	tvl	twi	tyv	udm	uga	uig	ukr	umb	und	urd	uzb	vai	ven	vie	vol	vot	wak	wal	war	was	wel	wen	wln	wol	xal	xho	yao	yap	yid	yor	ypk	zap	zbl	zen	zha	znd	zul	zun	zxx	zza");
        
        // fill the obsolete Language Codes array
        $this->obsoleteLanguageCodes = explode("\t", "ajm	esk	esp	eth	far	fri	gag	gua	int	iri	cam	kus	mla	max	mol	lan	gal	lap	sao	gae	scc	scr	sho	snh	sso	swz	tag	taj	tar	tru	tsw");
        
        // fill the valid Country Codes array
        $this->countryCodes = explode("\t", "aca	af 	alu	aku	aa 	abc	ae 	as 	an 	ao 	am 	ay 	aq 	ag 	azu	aru	ai 	aw 	at 	au 	aj 	bf 	ba 	bg 	bb 	bw 	be 	bh 	dm 	bm 	bt 	bo 	bn 	bs 	bv 	bl 	bcc	bi 	vb 	bx 	bu 	uv 	br 	bd 	cau	cb 	cm 	xxc	cv 	cj 	cx 	cd 	cl 	cc 	ch 	xa 	xb 	ck 	cou	cq 	cf 	cg 	ctu	cw 	cr 	ci 	cu 	cy 	xr 	iv 	deu	dk 	dcu	ft 	dq 	dr 	em 	ec 	ua 	es 	enk	eg 	ea 	er 	et 	fk 	fa 	fj 	fi 	flu	fr 	fg 	fp 	go 	gm 	gz 	gau	gs 	gw 	gh 	gi 	gr 	gl 	gd 	gp 	gu 	gt 	gv 	pg 	gy 	ht 	hiu	hm 	ho 	hu 	ic 	idu	ilu	ii 	inu	io 	iau	ir 	iq 	iy 	ie 	is 	it 	jm 	ja 	ji 	jo 	ksu	kv 	kz 	kyu	ke 	gb 	kn 	ko 	ku 	kg 	ls 	lv 	le 	lo 	lb 	ly 	lh 	li 	lau	lu 	xn 	mg 	meu	mw 	my 	xc 	ml 	mm 	mbc	xe 	mq 	mdu	mau	mu 	mf 	ot 	mx 	miu	fm 	xf 	mnu	msu	mou	mv 	mc 	mp 	mtu	mj 	mr 	mz 	sx 	nu 	nbu	np 	ne 	na 	nvu	nkc	nl 	nhu	nju	nmu	nyu	nz 	nfc	nq 	ng 	nr 	xh 	xx 	nx 	ncu	ndu	nik	nw 	ntc	no 	nsc	nuc	ohu	oku	mk 	onc	oru	pk 	pw 	pn 	pp 	pf 	py 	pau	pe 	ph 	pc 	pl 	po 	pic	pr 	qa 	qea	quc	riu	rm 	ru 	rw 	re 	xj 	xd 	xk 	xl 	xm 	ws 	sm 	sf 	snc	su 	stk	sg 	rb 	mo 	se 	sl 	si 	xo 	xv 	bp 	so 	sa 	scu	sdu	xs 	sp 	sh 	xp 	ce 	sj 	sr 	sq 	sw 	sz 	sy 	ta 	tz 	tnu	fs 	txu	th 	tg 	tl 	tma	to 	tr 	ti 	tu 	tk 	tc 	tv 	ug 	un 	ts 	xxk	uik	xxu	uc 	up 	uy 	utu	uz 	nn 	vp 	vc 	ve 	vtu	vm 	vi 	vau	vra	wea	wk 	wlk	wf 	wau	wj 	wvu	ss 	wiu	wyu	xga	xna	xoa	xra	ye 	ykc	za 	rh ");
        
        // fill the obsolete Country Codes array
        $this->obsoleteCountryCodes = explode("\t", "ai 	air	ac 	ajr	bwr	cn 	cz 	cp 	ln 	cs 	err	gsr	ge 	gn 	hk 	iw 	iu 	jn 	kzr	kgr	lvr	lir	mh 	mvr	nm 	pt 	rur	ry 	xi 	sk 	xxr	sb 	sv 	tar	tt 	tkr	unr	uk 	ui 	us 	uzr	vn 	vs 	wb 	ys 	yu ");
        
        // the codes cash, lcsh, lcshac, mesh, nal, and rvm are covered by 2nd
        // indicators in 600-655
        // they are only used when indicators are not available
        $this->sources600_651 = explode("\t", "aass	aat	abne	afset	agrifors	agrovoc	agrovocf	agrovocs	aiatsisl	aiatsisp	aiatsiss	aktp	albt	allars	amg	apaist	asft	asrcrfcd	asrcseo	asrctoa	asth	atla	aucsh	barn	bella	bet	bgtchm	bhammf	bhashe	bibalex	biccbmc	bicssc	bidex	bisacsh	bisacmt	bisacrt	blmlsh	bt	cabt	cash	cct	ccte	cctf	ceeus	chirosh	cht	ciesiniv	cilla	conorsi	csahssa	csalsct	csapa	csh	csht	cstud	czenas	dacs	dcs	ddcrit	dissao	dit	drama	dtict	ebfem	eclas	eet	eflch	eks	embne	ept	ericd	est	eurovocen	eurovocsl	fast	fgtpcm	finmesh	fire	fmesh	fnhl	francis	galestne	gem	georeft	gst	gtt	hapi	hkcan	helecon	henn	hlasstg	hoidokki	huc	iaat	ica	icpsr	idas	iescs	iest	ilot	ilpt	inist	inspect	ipat	ipsp	isis	itglit	itoamc	itrt	jhpb	jhpk	jlabsh	kaa	kao	kaunokki	kdm	kitu	kkts	kssbar	kta	ktpt	ktta	kula	kupu	lacnaf	larpcal	lcsh	lcshac	lcstt	lctgm	lemac	lemb	liv	lnmmbr	local	ltcsh	lua	maaq	mar	masa	mech	mesh	mipfesd	mmm	mpirdes	msh	mtirdes	musa	muzeukc	muzeukn	muzeukv	muzvukci	nal	nalnaf	nasat	ncjt	ndllsh	nicem	nimacsc	nlgaf	nlgkk	nlgsh	nlmnaf	nsbncf	ntcpsc	ntcsd	ntissc	nzggn	nznb	ogst	onet	opms	pascal	peri	pha	pkk	pmbok	pmcsg	pmt	poliscit	popinte	precis	prvt	psychit	quiding	qlsp	qrma	qrmak	qtglit	raam	ram	rasuqam	renib	reo	rero	rerovoc	reveal	rma	rpe	rswk	rswkaf	rugeo	rurkp	rvm	sao	sbiao	scbi	scgdst	scisshl	scot	sears	sfit	sgc	sgce	shbe	she	sigle	sipri	sk	skon	slem	smda	snt	socio	sosa	spines	ssg	swd	swemesh	taika	taxhs	tbit	tesa	test	tgn	tho	thub	tlka	tlsh	toit	trt	trtsa	tsht	ttka	tucua	ulan	umitrist	unbisn	unbist	unescot	usaidt	vmj	waqaf	watrest	wgst	wot	wpicsh	ysa");
        $this->obsoleteSources600_651 = explode("\t", "cash	lcsh	lcshac	mesh	nal	reroa	rvm");
        $this->sources655 = explode("\t", "aat	afset	aiatsisl	aiatsisp	aiatsiss	aktp	amg	asrcrfcd	asrcseo	asrctoa	asth	aucsh	barn	bibalex	biccbmc	bgtchm	bisacsh	bisacmt	bisacrt	bt	cash	chirosh	cct	conorsi	csht	czenas	dacs	dcs	dct	eet	eflch	embne	ept	ericd	estc	eurovocen	eurovocsl	fast	fbg	finmesh	fire	galestne	gem	gmgpc	gsafd	gst	gtlm	hapi	hkcan	hoidokki	ica	ilot	itglit	itrt	jhpb	jhpk	kkts	lacnaf	lcsh	lcshac	lcstt	lctgm	lemac	local	maaq	mar	marcgt	mech	mesh	migfg	mim	msh	muzeukc	muzeukn	muzeukv	muzvukci	nal	nalnaf	ngl	nimafc	nlgaf	nlgkk	nlgsh	nlmnaf	nmc	nsbncf	nzggn	nznb	onet	opms	pkk	pmcsg	pmt	quiding	qlsp	qrmak	qtglit	raam	radfg	rbbin	rbgenr	rbpap	rbpri	rbprov	rbpub	rbtyp	reo	rerovoc	reveal	rma	rswk	rswkaf	rugeo	rvm	sao	scbi	sears	sgc	sgce	sgp	sipri	skon	snt	socio	spines	ssg	swd	swemesh	tbit	tesa	tho	thub	toit	tsht	tucua	ulan	vmj	waqaf");
        $this->obsoleteSources655 = explode("\t", "cash	ftamc	lcsh	lcshac	mesh	nal	reroa	rvm");
        // @codingStandardsIgnoreEnd
    }
    // }}}
}
// }}}
