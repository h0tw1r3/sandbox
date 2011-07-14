<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="xml" indent="yes" encoding="UTF-8" omit-xml-declaration="yes"/>
  <xsl:variable name="shift-width" select="16"/>

  <xsl:template match="/">
    <div class="lshw">
      <ul>
        <xsl:apply-templates select="node" />
      </ul>
    </div>
  </xsl:template>

  <xsl:template match="node">
    <xsl:variable name="styles">
      <xsl:if test="position()=last()"> last</xsl:if>
      <xsl:if test="position()=1"> first</xsl:if>
    </xsl:variable>
    <li class="{@class}{$styles}">
      <xsl:variable name="displaytext">
        <xsl:choose>
          <xsl:when test="@class = 'processor' and size">
            <xsl:call-template name="prettyBits">
              <xsl:with-param name="hertz" select="size" />
            </xsl:call-template><xsl:text> </xsl:text><xsl:value-of select="description" />
          </xsl:when>
          <xsl:when test="(@class = 'memory' or @class = 'volume' or @class = 'disk') and size">
            <xsl:call-template name="prettyBytes">
              <xsl:with-param name="bytes" select="size" />
            </xsl:call-template><xsl:text> </xsl:text><xsl:value-of select="description" />
          </xsl:when>
          <xsl:when test="(@class = 'memory' or @class = 'volume' or @class = 'disk') and capacity">
            <xsl:call-template name="prettyBytes">
              <xsl:with-param name="bytes" select="capacity" />
            </xsl:call-template><xsl:text> </xsl:text><xsl:value-of select="description" />
          </xsl:when>
          <xsl:when test="@class = 'memory'">(empty)</xsl:when>
          <xsl:when test="@class = 'network' and capacity">
            <xsl:call-template name="prettyBits">
              <xsl:with-param name="bits" select="capacity" />
            </xsl:call-template><xsl:text> </xsl:text><xsl:value-of select="description" />
          </xsl:when>
          <xsl:when test="@class = 'disk' and @id = 'medium' and configuration/setting/@value = 'mounted'">
            <xsl:value-of select="logicalname[last()]" />
          </xsl:when>
          <xsl:when test="@class = 'disk' and @id = 'medium'">
              (media not mounted)
          </xsl:when>
          <xsl:when test="@class = 'network'">
            <xsl:value-of select="description" />
          </xsl:when>
          <xsl:when test="description">
            <xsl:value-of select="description" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="product" />
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
      <xsl:variable name="pseudoclass">
        <xsl:if test="@class = 'volume' and node">-container</xsl:if>
        <xsl:if test="@class = 'volume' and not(node) and not(configuration/setting/@value = 'mounted')">-unmounted</xsl:if>
        <xsl:if test="@class = 'storage' and @id = 'usb'">-usb</xsl:if>
        <xsl:if test="@class = 'disk' and @id='medium' and ../capabilities/capability/@id = 'atapi'">-disc</xsl:if>
        <xsl:if test="@class = 'communication' and capabilities/capability/@id = 'bluetooth'">-bluetooth</xsl:if>
        <xsl:if test="@class = 'network' and capabilities/capability/@id = 'wireless'">-wireless</xsl:if>
        <xsl:if test="@class = 'bus' and starts-with(@id,'usb')">-usb</xsl:if>
        <xsl:if test="@class = 'bus' and starts-with(@id,'firewire')">-firewire</xsl:if>
        <xsl:if test="@class = 'bridge' and capabilities/capability/@id = 'pcmcia'">-pcmcia</xsl:if>
        <xsl:if test="@class = 'power' and starts-with(@id,'battery')">-battery</xsl:if>
        <xsl:if test="@class = 'system' and contains(description,'Portable')">-portable</xsl:if>
        <xsl:if test="@class = 'input' and (contains(description,'Mouse') or contains(product,'Mouse'))">-mouse</xsl:if>
        <xsl:if test="@class = 'input' and (contains(description,'Keyboard') or contains(product,'Keyboard'))">-keyboard</xsl:if>
      </xsl:variable>
      <span class="class sprite-{@class}{$pseudoclass}"><xsl:value-of select="@class" /> &#183;</span>
      <span class="name" title="{product}"><xsl:value-of select="$displaytext" /></span>
      <xsl:if test="node">
        <ul>
          <xsl:apply-templates select="node" />
        </ul>
      </xsl:if>
    </li>
  </xsl:template>

  <xsl:template name="prettyBytes">
    <xsl:param name="bytes" />
    <xsl:variable name="filesize">
      <xsl:if test="string-length($bytes) &gt; 0">
        <xsl:if test="number($bytes) &gt; 0">
          <xsl:choose>
            <xsl:when test="floor($bytes div 1024) &lt; 1"><xsl:value-of select="$bytes" />B</xsl:when>
            <xsl:when test="floor($bytes div 1024 div 1024) &lt; 1"><xsl:value-of select="format-number(($bytes div 1024), '#.##')" />KB</xsl:when>
            <xsl:when test="floor($bytes div 1024 div 1024 div 1024) &lt; 1"><xsl:value-of select="format-number(($bytes div 1024 div 1024), '#.##')" />MB</xsl:when>
            <xsl:when test="floor($bytes div 1024 div 1024 div 1024 div 1024) &lt; 1"><xsl:value-of select="format-number(($bytes div 1024 div 1024 div 1024), '#.##')" />GB</xsl:when>
            <xsl:otherwise><xsl:value-of select="format-number(($bytes div 1024 div 1024 div 1024 div 1024), '#.##')" />TB</xsl:otherwise>
          </xsl:choose>
        </xsl:if>
      </xsl:if>
    </xsl:variable>
    <xsl:value-of select="$filesize" />
  </xsl:template>

  <xsl:template name="prettyBits">
    <xsl:param name="bits" />
    <xsl:param name="hertz" />

    <xsl:variable name="reduce">
      <xsl:choose>
        <xsl:when test="$bits">
          <xsl:copy-of select="number($bits) + 0" />
        </xsl:when>
        <xsl:otherwise>
          <xsl:copy-of select="number($hertz) + 0" />
        </xsl:otherwise>
      </xsl:choose>
    </xsl:variable>

    <xsl:variable name="reduced">
      <xsl:if test="string-length($reduce) &gt; 0">
        <xsl:if test="number($reduce) &gt; 0">
          <xsl:choose>
            <xsl:when test="round($reduce div 1000) &lt; 1"><xsl:value-of select="$reduce" /></xsl:when>
            <xsl:when test="round($reduce div 1000 div 1000) &lt; 1"><xsl:value-of select="format-number(($reduce div 1000), '#.#')" />K</xsl:when>
            <xsl:when test="round($reduce div 1000 div 1000 div 1000) &lt; 1"><xsl:value-of select="format-number(($reduce div 1000 div 1000),'#.#')" />M</xsl:when>
            <xsl:otherwise><xsl:value-of select="format-number(($reduce div 1000 div 1000 div 1000),'#.#')" />G</xsl:otherwise>
          </xsl:choose>
        </xsl:if>
      </xsl:if>
    </xsl:variable>

    <xsl:value-of select="$reduced" />
    <xsl:choose>
      <xsl:when test="$bits">bit</xsl:when>
      <xsl:otherwise>hz</xsl:otherwise>
    </xsl:choose>

  </xsl:template>

</xsl:stylesheet>
